<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */
namespace Vsm\VsmHelperTools\Controller;

use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Contao\MemberModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Contao\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\PaymentIntent;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Event;
use Vsm\VsmHelperTools\Service\StripePaymentService;
use Vsm\VsmHelperTools\Service\MemberService;
use Vsm\VsmHelperTools\Service\PaymentProcessorService;
use Vsm\VsmHelperTools\Service\EmailHelper;
use Contao\Email;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

class StripeWebhookController extends AbstractController
{
    private ContaoFramework $framework;
    private LoggerInterface $logger;
    private string $webhookSecret;
    private string $stripeSecretKey;
    private StripePaymentService $stripeService;
    private MemberService $memberService;
    private PaymentProcessorService $paymentProcessorService;
    private string $projectDir;
    private Connection $db;

    public function __construct(
        ContaoFramework $framework,
        LoggerInterface $logger,
        string $webhookSecret,
        string $stripeSecretKey,
        StripePaymentService $stripeService,
        MemberService $memberService,
        PaymentProcessorService $paymentProcessorService,
        string $projectDir,
        Connection $db
    ) {
        $this->framework = $framework;
        $this->logger = $logger;
        $this->webhookSecret = $webhookSecret;
        $this->stripeSecretKey = $stripeSecretKey;
        $this->stripeService = $stripeService;
        $this->memberService = $memberService;
        $this->paymentProcessorService = $paymentProcessorService;
        $this->projectDir = $projectDir;
        $this->db = $db;
    }

    #[Route('/check-user', name: 'stripe_check_user', methods: ['POST'])]
    public function checkUser(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? '';
            $username = $data['username'] ?? '';

            $this->framework->initialize();

            $existingEmail = MemberModel::findByEmail($email);
            $existingUsername = MemberModel::findByUsername($username);

            // Wenn beide nicht existieren, ist alles OK
            if (!$existingEmail && !$existingUsername) {
                return new JsonResponse([
                    'valid' => true
                ]);
            }

            // Wenn beide existieren, müssen sie zum gleichen Benutzer gehören
            if ($existingEmail && $existingUsername) {
                $valid = ($existingEmail->id === $existingUsername->id);
                return new JsonResponse([
                    'valid' => $valid,
                    'message' => $valid ? 'ok' : 'Die E-Mail-Adresse und der Benutzername gehören zu verschiedenen Konten. Bitte verwenden Sie entweder beide Daten eines bestehenden Kontos oder geben Sie komplett neue Daten ein.'
                ]);
            }

            // Wenn nur einer existiert, ist es nicht gültig
            if ($existingEmail) {
                return new JsonResponse([
                    'valid' => false,
                    'message' => 'Diese E-Mail-Adresse wird bereits verwendet. Bitte wählen Sie eine andere oder melden Sie sich an.'
                ]);
            }

            if ($existingUsername) {
                return new JsonResponse([
                    'valid' => false,
                    'message' => 'Dieser Benutzername wird bereits verwendet. Bitte wählen Sie einen anderen.'
                ]);
            }

            // Sollte nie erreicht werden
            return new JsonResponse([
                'valid' => false,
                'message' => 'Unbekannter Fehler bei der Überprüfung.'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Fehler bei der Überprüfung: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Verarbeitet den Webhook-Request von Stripe
     */
    #[Route('/webhook', name: 'stripe_webhook', methods: ['POST'], options: ['expose' => true], defaults: ['_token_check' => false])]
    public function handleWebhook(Request $request): Response
    {
        try {
            // Protokolliere den Eingang des Webhooks
            $this->logger->info('Stripe Webhook eingegangen', [
                'content_type' => $request->headers->get('Content-Type'),
                'signature' => $request->headers->has('Stripe-Signature') ? 'vorhanden' : 'fehlt'
            ]);
            
            // Gesamter Webhook-Payload für Debugging
            $payload = $request->getContent();
            $this->logger->debug('Stripe Webhook Payload', [
                'payload_length' => strlen($payload),
                'payload_start' => substr($payload, 0, 100) . '...'
            ]);
            
            // Signatur validieren und Event konstruieren
            $event = $this->validateWebhookSignature($request);
            if (!$event) {
                $this->logger->error('Ungültige Webhook-Signatur');
                return new Response('Webhook signature verification failed', 400);
            }
            
            $this->logger->info('Webhook-Event validiert', [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'api_version' => $event->api_version
            ]);
            
            // Event verarbeiten
            $result = $this->processWebhookEvent($event);
            
            // Erfolgsantwort
            return new JsonResponse($result);
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Webhook-Verarbeitung: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Bei Fehlern 200 zurückgeben, damit Stripe den Webhook als verarbeitet betrachtet
            // und nicht erneut sendet
            return new Response('Webhook processing failed: ' . $e->getMessage(), 200);
        }
    }
    
    /**
     * Validiert die Signature des Webhook-Events
     */
    private function validateWebhookSignature(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        
        if (empty($payload) || empty($sigHeader)) {
            throw new \Exception('Missing payload or signature');
        }
        
        $webhookSecret = $this->stripeService->getWebhookSecret();
        
        try {
            // Verifiziere die Stripe Webhook-Signatur
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $webhookSecret
            );
            
            $this->logger->info('Webhook event validated: ' . $event->type);
            return $event;
        } catch (\UnexpectedValueException $e) {
            throw new \Exception('Invalid payload: ' . $e->getMessage());
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            throw new \Exception('Invalid signature: ' . $e->getMessage());
        }
    }
    
    /**
     * Verarbeitet ein Webhook-Event basierend auf dem Typ
     */
    private function processWebhookEvent($event)
    {
        $this->logger->info('Processing webhook event', [
            'event_type' => $event->type,
            'event_id' => $event->id
        ]);
        
        switch ($event->type) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentIntentSucceeded($event->data->object);
                
            case 'payment_intent.canceled':
                return $this->handlePaymentIntentCanceled($event->data->object);
                
            case 'charge.succeeded':
                return $this->handleChargeSucceeded($event->data->object);
                
            default:
                $this->logger->info('Unhandled event type: ' . $event->type);
                return [
                    'success' => true,
                    'message' => 'Event received but not processed',
                    'event_type' => $event->type
                ];
        }
    }
    
    /**
     * Verarbeitet ein payment_intent.succeeded Event
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        $this->logger->info('Payment Intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100,
            'currency' => $paymentIntent->currency,
            'has_metadata' => !empty($paymentIntent->metadata) ? 'yes' : 'no'
        ]);
        
        // Nach dem Umstieg auf JavaScript API wird das E-Mail-Template aus den Metadaten ausgelesen
        if (!empty($paymentIntent->metadata->personal_data)) {
            // Personendaten aus dem Metadaten-JSON extrahieren
            $personalData = json_decode($paymentIntent->metadata->personal_data, true);
            $productData = !empty($paymentIntent->metadata->product_data) ? 
                json_decode($paymentIntent->metadata->product_data, true) : 
                [];
            
            // E-Mail-Template auswählen
            $emailTemplate = $paymentIntent->metadata->email_template ?? null;
            
            if ($personalData && isset($personalData['email'])) {
                // Verarbeite Benutzer-Erstellung, wenn gewünscht
                if (!empty($paymentIntent->metadata->create_user) && $paymentIntent->metadata->create_user === 'true') {
                    $this->logger->info('Creating user account', [
                        'email' => $personalData['email']
                    ]);
                    
                    try {
                        // Benutzer erstellen über den PaymentProcessor
                        $this->paymentProcessorService->createUser($personalData);
                    } catch (\Exception $e) {
                        $this->logger->error('Error creating user: ' . $e->getMessage());
                    }
                }
                
                // Prüfe, ob Dateidownload aktiviert ist
                $downloadLink = null;
                if (!empty($productData['file_sale']) && !empty($productData['file_path'])) {
                    try {
                        $downloadToken = $productData['download_token'] ?? '';
                        $downloadExpires = $productData['download_expires'] ?? 7;
                        $downloadLimit = $productData['download_limit'] ?? 3;
                        
                        // Download-Eintrag erstellen und Link generieren
                        $downloadLink = $this->paymentProcessorService->processFileDownload(
                            $productData, 
                            $paymentIntent->id, 
                            $personalData['email']
                        );
                    } catch (\Exception $e) {
                        $this->logger->error('Error processing file download: ' . $e->getMessage());
                    }
                }
                
                // E-Mail senden
                $this->sendEmailNotification(
                    $personalData,
                    $paymentIntent,
                    $emailTemplate,
                    $productData,
                    $downloadLink
                );
            } else {
                $this->logger->error('Missing email in personal data for payment intent: ' . $paymentIntent->id);
            }
        } else {
            // Fallback für alte Payment Intents ohne Metadaten
            $this->logger->warning('Payment intent without metadata, using fallback processing', [
                'payment_intent_id' => $paymentIntent->id
            ]);
            
            // Alte Methode aufrufen, falls noch benötigt
            return $this->paymentProcessorService->processPaymentIntent($paymentIntent);
        }
        
        return [
            'success' => true,
            'message' => 'Payment intent processed successfully',
            'payment_intent_id' => $paymentIntent->id
        ];
    }
    
    /**
     * Verarbeitet ein payment_intent.canceled Event
     */
    private function handlePaymentIntentCanceled($paymentIntent)
    {
        $this->logger->info('Payment Intent canceled', [
            'payment_intent_id' => $paymentIntent->id,
            'cancellation_reason' => $paymentIntent->cancellation_reason ?? 'unknown',
            'status' => $paymentIntent->status,
            'amount' => $paymentIntent->amount / 100 . ' ' . strtoupper($paymentIntent->currency),
            'created' => date('Y-m-d H:i:s', $paymentIntent->created),
            'last_payment_error' => $paymentIntent->last_payment_error ? json_encode($paymentIntent->last_payment_error) : 'null'
        ]);
        
        // Prüfen ob der PaymentIntent von Stripe automatisch storniert wurde
        if ($paymentIntent->status === 'canceled' && empty($paymentIntent->cancellation_reason)) {
            $this->logger->warning('Payment Intent wurde automatisch von Stripe storniert', [
                'payment_intent_id' => $paymentIntent->id,
                'timestamps' => [
                    'created' => date('Y-m-d H:i:s', $paymentIntent->created),
                    'canceled' => date('Y-m-d H:i:s', $paymentIntent->canceled_at ?? time())
                ]
            ]);
        }
        
        // Wir können hier die bestehende Logik wiederverwenden
        return $this->paymentProcessorService->processPaymentIntentCanceled($paymentIntent);
    }
    
    /**
     * Verarbeitet ein charge.succeeded Event
     */
    private function handleChargeSucceeded($charge)
    {
        $this->logger->info('Charge succeeded', [
            'charge_id' => $charge->id,
            'amount' => $charge->amount / 100,
            'currency' => $charge->currency
        ]);
        
        // Optional zusätzliche Logik für erfolgreiche Zahlungen
        
        return [
            'success' => true,
            'message' => 'Charge processed successfully',
            'charge_id' => $charge->id
        ];
    }
    
    /**
     * Sendet eine E-Mail-Benachrichtigung für eine erfolgreiche Zahlung
     */
    private function sendEmailNotification(array $personalData, $paymentIntent, $emailTemplate, array $productData, $downloadLink = null)
    {
        try {
            // Preisdaten formatieren
            $amount = $paymentIntent->amount / 100;
            $formattedAmount = number_format($amount, 2, ',', '.');
            $currency = strtoupper($paymentIntent->currency);
            
            // Download-Ablaufdatum berechnen
            $expiryTimestamp = time() + 60*60*24*intval($productData['download_expires'] ?? 7);
            $validUntil = date('d.m.Y', $expiryTimestamp);
            
            // E-Mail-Daten vorbereiten
            $emailData = [
                'order_id' => $paymentIntent->id,
                'product_name' => $productData['title'] ?? $paymentIntent->metadata->product_title ?? 'Produkt',
                'product_price' => $formattedAmount . ' ' . $currency,
                'customer_name' => trim(($personalData['firstname'] ?? '') . ' ' . ($personalData['lastname'] ?? '')),
                'customer_email' => $personalData['email'],
                'download_expires' => $productData['download_expires'] ?? 7,
                'download_limit' => $productData['download_limit'] ?? 3
            ];
            
            // Download-Link hinzufügen, wenn vorhanden
            if ($downloadLink) {
                $emailData['download_link'] = $downloadLink;
                
                // Download-Anweisungen in HTML
                $emailData['download_instructions'] = '
                <div style="background-color: #e8f4fc; border: 1px solid #b8e0f7; padding: 15px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #2980b9;">Download-Informationen</h3>
                    <p>Sie können Ihr gekauftes Produkt jetzt herunterladen. Klicken Sie auf den folgenden Link:</p>
                    <p><a href="' . $downloadLink . '" style="color: #2980b9; font-weight: bold;">Download starten</a></p>
                    <p style="font-size: 0.9em; color: #666;">
                        <strong>Wichtig:</strong> Dieser Download-Link ist gültig für <strong>' . $emailData['download_expires'] . '</strong> Tage 
                        und kann maximal <strong>' . $emailData['download_limit'] . '</strong> mal verwendet werden.
                    </p>
                </div>';
            }
            
            // Versuch, die Contao-Konfiguration zu laden
            $this->framework->initialize();
            $config = \Contao\Config::all();
            
            // Absender ermitteln - mit Fallback für Fehler
            $emailFrom = $config['adminEmail'] ?? 'shop@contao5.vossmedien.de';
            $emailFromName = $config['websiteTitle'] ?? 'VossMedian Online-Shop';
            $emailSubject = 'Ihre Bestellung wurde erfolgreich abgeschlossen';
            
            $this->logger->info('E-Mail-Versandversuch', [
                'to' => $personalData['email'],
                'from' => $emailFrom,
                'subject' => $emailSubject,
                'template_id' => $emailTemplate,
                'has_download' => $downloadLink ? 'ja' : 'nein'
            ]);
            
            // Einfachen direkten E-Mail-Versand versuchen
            try {
                // Standard-E-Mail-Inhalt generieren, wenn kein Template-Inhalt gefunden wird
                $emailContent = $this->generateDefaultEmailContent($emailData);
                
                // E-Mail erstellen
                $email = new Email();
                $email->from = $emailFrom;
                $email->fromName = $emailFromName;
                $email->subject = $emailSubject;
                $email->html = $emailContent;
                $email->text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $emailContent));
                
                // E-Mail senden
                $email->sendTo($personalData['email']);
                
                $this->logger->info('Standard-E-Mail erfolgreich gesendet', [
                    'to' => $personalData['email'],
                    'from' => $emailFrom,
                    'subject' => $emailSubject
                ]);
                
                return true;
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim direkten E-Mail-Versand', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            // Versuch, die E-Mail über den Notification Center zu senden (falls direkter Versand fehlschlägt)
            if ($emailTemplate) {
                try {
                    $this->logger->info('Versuche E-Mail über Notification Center zu senden', [
                        'template_id' => $emailTemplate
                    ]);
                    
                    $result = $this->paymentProcessorService->sendContaoNotification(intval($emailTemplate), $emailData);
                    
                    if ($result) {
                        $this->logger->info('E-Mail über Notification Center gesendet');
                        return true;
                    } else {
                        $this->logger->warning('Notification Center konnte keine E-Mail senden');
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Fehler beim Senden über Notification Center', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // Wenn alles fehlschlägt, versuchen wir es mit dem PaymentProcessor-Service
            try {
                $adaptedEmailData = [
                    'firstName' => $personalData['firstname'] ?? '',
                    'lastName' => $personalData['lastname'] ?? '',
                    'productTitle' => $emailData['product_name'],
                    'amount' => $formattedAmount,
                    'currency' => $currency,
                    'paymentId' => $paymentIntent->id,
                    'email' => $personalData['email']
                ];
                
                if ($downloadLink) {
                    $adaptedEmailData['downloadLink'] = $downloadLink;
                    $adaptedEmailData['validUntil'] = $validUntil;
                }
                
                $this->logger->info('Versuche E-Mail über PaymentProcessor zu senden');
                $result = $this->paymentProcessorService->sendStandardEmail($personalData['email'], $adaptedEmailData);
                
                if ($result) {
                    $this->logger->info('E-Mail über PaymentProcessor gesendet');
                    return true;
                } else {
                    $this->logger->warning('PaymentProcessor konnte keine E-Mail senden');
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Senden über PaymentProcessor', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            // Alle Versuche sind fehlgeschlagen
            $this->logger->error('Alle E-Mail-Versandversuche sind fehlgeschlagen');
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Kritischer Fehler bei der E-Mail-Benachrichtigung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Generiert ein Standard-E-Mail-Template für Bestellbestätigungen
     */
    private function generateDefaultEmailContent(array $emailData): string
    {
        $html = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333;">
            <h2 style="color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">Bestellbestätigung</h2>
            
            <p>Sehr geehrte/r ' . ($emailData['customer_name'] ?: 'Kunde') . ',</p>
            
            <p>vielen Dank für Ihren Einkauf. Ihre Bestellung mit der Nummer <strong>' . $emailData['order_id'] . '</strong> wurde erfolgreich abgeschlossen.</p>
            
            <div style="background-color: #f9f9f9; border: 1px solid #eee; padding: 15px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #2c3e50;">Bestellübersicht</h3>
                <p>
                    <strong>Produkt:</strong> ' . ($emailData['product_name'] ?: 'Produkt') . '<br>
                    <strong>Preis:</strong> ' . ($emailData['product_price'] ?: '0,00 EUR') . '
                </p>
            </div>';
            
        // Download-Anweisungen, falls vorhanden
        if (!empty($emailData['download_instructions'])) {
            $html .= $emailData['download_instructions'];
        }
        
        $html .= '
            <p style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                Mit freundlichen Grüßen<br>
                Ihr Team
            </p>
        </div>';
        
        return $html;
    }
    
    /**
     * Sendet eine E-Mail über die Contao Email-Klasse
     */
    private function sendEmail(string $to, string $subject, string $htmlContent, string $from, string $fromName): void
    {
        try {
            $email = new Email();
            $email->from = $from;
            $email->fromName = $fromName;
            $email->subject = $subject;
            $email->html = $htmlContent;
            
            // Klartext-Version erstellen
            $email->text = strip_tags(str_replace(['<br>', '<br />', '<br/>', '</p>'], "\n", $htmlContent));
            
            $this->logger->info('Sende E-Mail an ' . $to);
            $email->sendTo($to);
            $this->logger->info('E-Mail erfolgreich gesendet');
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der E-Mail', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createContaoUser(array $metadata): void
    {
        $this->framework->initialize();

        try {
            $container = System::getContainer();
            $logger = $container->get('monolog.logger.contao');

            // Validierung der Eingabedaten
            if (empty($metadata['personalData'])) {
                throw new \Exception('No personal data provided');
            }

            $personalData = json_decode($metadata['personalData'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON in personalData: ' . json_last_error_msg());
            }

            // Pflichtfelder überprüfen
            $requiredFields = ['username', 'password', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($personalData[$field])) {
                    throw new \Exception("Required field missing: $field");
                }
            }

            // E-Mail-Format prüfen
            if (!filter_var($personalData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email format');
            }

            // Überprüfe, ob Benutzer bereits existiert
            $existingUser = MemberModel::findByEmail($personalData['email']);
            if ($existingUser) {
                throw new \Exception('User with this email already exists');
            }

            $existingUsername = MemberModel::findByUsername($personalData['username']);
            if ($existingUsername) {
                throw new \Exception('User with this username already exists');
            }

            $productData = [];
            if (!empty($metadata['productData'])) {
                $productData = json_decode($metadata['productData'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $logger->warning('Invalid JSON in productData', [
                        'context' => ContaoContext::GENERAL,
                        'error' => json_last_error_msg()
                    ]);
                    $productData = [];
                }
            }

            // Verwende duration statt subscription_duration
            $duration = isset($productData['duration']) ? (int)$productData['duration'] : 0;

            // Start ist jetzt
            $startTime = time();

            // Berechne stopTime
            if ($duration > 0) {
                $stopTime = strtotime("+{$duration} months", $startTime);
            } else {
                $stopTime = 0;
            }

            $logger->info('Creating new member', [
                'context' => ContaoContext::GENERAL,
                'username' => $personalData['username'],
                'start' => date('Y-m-d H:i:s', $startTime),
                'stop' => $stopTime ? date('Y-m-d H:i:s', $stopTime) : 'not set',
                'duration' => $duration
            ]);

            // Sicheres Passwort-Hashing
            $passwordHash = password_hash(
                base64_decode($personalData['password']),
                PASSWORD_DEFAULT,
                ['cost' => 12]
            );

            // Neuen Benutzer anlegen
            $member = new MemberModel();
            $member->tstamp = time();
            $member->dateAdded = time();
            $member->username = $personalData['username'];
            $member->password = $passwordHash;
            $member->email = $personalData['email'];
            $member->firstname = $personalData['firstname'] ?? '';
            $member->lastname = $personalData['lastname'] ?? '';
            $member->gender = $this->mapSalutation($personalData['salutation'] ?? '');
            $member->street = $personalData['street'] ?? '';
            $member->postal = $personalData['postal'] ?? '';
            $member->city = $personalData['city'] ?? '';
            $member->phone = $personalData['phone'] ?? '';
            $member->company = $personalData['company'] ?? '';
            $member->login = true;
            $member->start = $startTime;
            $member->stop = $stopTime;
            $member->dateEnd = $stopTime ? date('Y-m-d', $stopTime) : null; // Für die E-Mail

            // Sichere Behandlung der Benutzergruppen
            if (!empty($metadata['memberGroup']) && is_numeric($metadata['memberGroup'])) {
                $member->groups = serialize([(int)$metadata['memberGroup']]);
            } else {
                $logger->warning('No valid member group provided', [
                    'context' => ContaoContext::GENERAL,
                    'memberGroup' => $metadata['memberGroup'] ?? 'not set'
                ]);
                // Standardgruppe verwenden, falls konfiguriert
                $defaultGroup = System::getContainer()->getParameter('vsm_helper_tools.default_member_group') ?? null;
                if ($defaultGroup) {
                    $member->groups = serialize([(int)$defaultGroup]);
                }
            }

            $member->save();

            // Nach erfolgreicher Speicherung E-Mail und Plunk-Event senden
            try {
                $this->sendRegistrationEmail($member);
                $logger->info('Registration email sent successfully', [
                    'context' => ContaoContext::GENERAL,
                    'username' => $member->username,
                    'email' => $member->email
                ]);

            } catch (\Exception $e) {
                $logger->error('Failed to send registration email or Plunk event', [
                    'context' => ContaoContext::GENERAL,
                    'username' => $member->username,
                    'error' => $e->getMessage()
                ]);
            }

            $logger->info('User created successfully', [
                'context' => ContaoContext::GENERAL,
                'username' => $member->username
            ]);

        } catch (\Exception $e) {
            $logger->error('Error in user creation', [
                'context' => ContaoContext::GENERAL,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function mapSalutation(string $salutation): string
    {
        return match ($salutation) {
            'Herr' => 'male',
            'Frau' => 'female',
            default => 'other'
        };
    }

    private function sendRegistrationEmail(MemberModel $member): void
    {
        try {
            $container = System::getContainer();
            $logger = $container->get('monolog.logger.contao');

            // Email-Konfiguration aus der config.yml holen
            $config = $container->get('contao.framework')->getAdapter(\Contao\Config::class);
            $emailFrom = $config->get('adminEmail');

            if (empty($emailFrom)) {
                throw new \Exception('No admin email configured in Contao settings');
            }

            $email = new \Contao\Email();
            $email->from = $emailFrom;
            $email->fromName = $config->get('websiteTitle') ?: 'Ihre Website';
            $email->subject = 'Ihre Registrierung wurde erfolgreich abgeschlossen';

            // Debug-Log der Email-Konfiguration
            $logger->info('Email configuration', [
                'context' => ContaoContext::GENERAL,
                'from' => $emailFrom,
                'fromName' => $email->fromName
            ]);

            // HTML-Email Template
            $html = sprintf(
                '<h2>Willkommen bei uns!</h2>
            <p>Sehr geehrte(r) %s %s,</p>
            <p>Ihr Benutzerkonto wurde erfolgreich angelegt.</p>
            <p>Ihre Zugangsdaten:</p>
            <ul>
                <li>Benutzername: %s</li>
                <li>E-Mail: %s</li>
            </ul>
            <p>Sie können sich nun mit Ihrem Benutzernamen und Passwort einloggen.</p>',
                $member->firstname,
                $member->lastname,
                $member->username,
                $member->email
            );

            // Überprüfen Sie, ob stop ein Integer ist und größer als 0
            if ($member->stop && is_numeric($member->stop) && (int)$member->stop > 0) {
                $stopTimestamp = (int)$member->stop;
                $html .= sprintf(
                    '<p>Ihr Zugang ist gültig bis zum %s.</p>',
                    date('d.m.Y', $stopTimestamp)
                );
            }

            $html .= '<p>Mit freundlichen Grüßen<br>Ihr Team</p>';

            $email->html = $html;
            $email->text = strip_tags($html);

            // Debug-Log vor dem Senden
            $logger->info('Attempting to send registration email', [
                'context' => ContaoContext::GENERAL,
                'to' => $member->email,
                'from' => $email->from,
                'subject' => $email->subject,
                'stop_time' => $member->stop
            ]);

            $email->sendTo($member->email);

            $logger->info('Email sent successfully', [
                'context' => ContaoContext::GENERAL,
                'to' => $member->email
            ]);

        } catch (\Exception $e) {
            $logger->error('Error sending registration email', [
                'context' => ContaoContext::GENERAL,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    #[Route('/stripe/create-payment-intent', name: 'stripe_create_payment_intent', methods: ['POST', 'OPTIONS'])]
    public function createPaymentIntent(Request $request): Response
    {
        $this->framework->initialize();
        
        try {
            // CORS-Header für alle Antworten
            $headers = [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With'
            ];
            
            // Prüfen Sie, ob es sich um eine OPTIONS-Anfrage handelt (CORS Preflight)
            if ($request->getMethod() === 'OPTIONS') {
                return new Response('', 200, $headers);
            }
            
            // Stripe-API initialisieren
            Stripe::setApiKey($this->stripeSecretKey);
            Stripe::setApiVersion('2025-02-24.acacia'); // Stabile API-Version

            // Prüfe den Content-Type
            if (!$request->headers->contains('Content-Type', 'application/json')) {
                throw new \Exception('Invalid Content-Type. Expected application/json');
            }

            // Daten aus dem Request-Body lesen
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \Exception('Invalid JSON data: ' . json_last_error_msg());
            }

            $this->logger->info('Erstelle PaymentIntent mit Daten:', [
                'amount' => $data['amount'] ?? 'nicht gesetzt',
                'currency' => $data['currency'] ?? 'nicht gesetzt',
                'productId' => $data['productId'] ?? 'nicht gesetzt',
                'productTitle' => $data['productTitle'] ?? 'nicht gesetzt',
                'elementId' => $data['elementId'] ?? 'nicht gesetzt'
            ]);

            // Validiere die Pflichtfelder
            if (empty($data['amount']) || !is_numeric($data['amount'])) {
                throw new \Exception('Amount is required and must be numeric');
            }
            if (empty($data['currency'])) {
                throw new \Exception('Currency is required');
            }

            // PersonalData vorbereiten - Bindestriche durch Unterstriche ersetzen
            $personalData = $data['personalData'] ?? [];
            $personalData = array_combine(
                array_map(function($key) {
                    return str_replace('-', '_', $key);
                }, array_keys($personalData)),
                array_values($personalData)
            );

            // Metadaten vorbereiten
            $metadata = [
                'product_id' => (string)($data['productId'] ?? ''),
                'product_title' => $data['productTitle'] ?? '',
                'element_id' => (string)($data['elementId'] ?? ''),
                'personal_data' => json_encode($personalData),
                'create_user' => isset($data['createUser']) ? ($data['createUser'] ? 'true' : 'false') : 'false',
                'email_template' => $data['emailTemplate'] ?? ''
            ];

            // Produktdaten hinzufügen und sicherstellen, dass alle erforderlichen Felder vorhanden sind
            if (!empty($data['productData'])) {
                $productData = $data['productData'];
                // Stelle sicher, dass alle erforderlichen Felder existieren
                $productData = array_merge([
                    'title' => '',
                    'price' => 0,
                    'file_sale' => false,
                    'file_id' => '',
                    'file_path' => '',
                    'download_token' => '',
                    'download_expires' => 7,
                    'download_limit' => 3
                ], $productData);
                $metadata['product_data'] = json_encode($productData);
            }

            $this->logger->info('Vorbereitete Metadaten:', $metadata);

            // PaymentIntent erstellen
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => (int)$data['amount'],
                'currency' => strtolower($data['currency']),
                'automatic_payment_methods' => [
                    'enabled' => true
                ],
                'metadata' => $metadata
            ]);

            $this->logger->info('PaymentIntent erfolgreich erstellt', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'client_secret' => $paymentIntent->client_secret
            ]);

            return new JsonResponse([
                'clientSecret' => $paymentIntent->client_secret,
                'id' => $paymentIntent->id
            ], 200, $headers);

        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen des PaymentIntent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data ?? null
            ]);

            return new JsonResponse([
                'error' => [
                    'message' => 'Fehler beim Erstellen des PaymentIntent: ' . $e->getMessage()
                ]
            ], 400, $headers);
        }
    }

    #[Route('/get-price', name: 'stripe_get_price', methods: ['POST'])]
    public function getPrice(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }

            // Validierung der Eingabedaten
            if (empty($data['amount']) || !is_numeric($data['amount'])) {
                throw new \Exception('Invalid amount');
            }
            if (empty($data['currency']) || !in_array(strtolower($data['currency']), ['eur', 'usd', 'gbp'])) {
                throw new \Exception('Invalid currency');
            }

            \Stripe\Stripe::setApiKey($this->stripeSecretKey);

            // Liste alle aktiven Preise
            $prices = \Stripe\Price::all([
                'active' => true,
                'type' => 'one_time',
                'currency' => strtolower($data['currency'])
            ]);

            // Suche nach passendem Preis
            foreach ($prices->data as $price) {
                if ($price->unit_amount == $data['amount']) {
                    return new JsonResponse(['priceId' => $price->id]);
                }
            }

            // Wenn kein passender Preis gefunden wurde, erstelle einen neuen
            $price = \Stripe\Price::create([
                'unit_amount' => (int)$data['amount'],
                'currency' => strtolower($data['currency']),
                'product_data' => [
                    'name' => 'One-time purchase'
                ]
            ]);

            return new JsonResponse(['priceId' => $price->id]);

        } catch (\Exception $e) {
            $this->logger->error('Price creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/create-setup-intent', name: 'stripe_create_setup_intent', methods: ['POST'])]
    public function createSetupIntent(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            \Stripe\Stripe::setApiKey($this->stripeSecretKey);
            \Stripe\Stripe::setApiVersion('2025-02-24.acacia');

            // Kunde erstellen oder abrufen
            $customer = $this->getOrCreateCustomer($data['personalData']);

            // Setup Intent erstellen
            $setupIntent = \Stripe\SetupIntent::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'metadata' => [
                    'product_id' => $data['productId'],
                    'product_title' => $data['productTitle'],
                    'element_id' => $data['elementId'],
                    'price_id' => $data['priceId']
                ]
            ]);

            // Nach erfolgreicher Bestätigung des SetupIntent wird die Subscription erstellt
            $this->session->set('pending_subscription', [
                'customer_id' => $customer->id,
                'price_id' => $data['priceId'],
                'product_id' => $data['productId'],
                'product_title' => $data['productTitle'],
                'element_id' => $data['elementId']
            ]);

            return new JsonResponse([
                'clientSecret' => $setupIntent->client_secret
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Setup intent creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function createUser(array $metadata): void
    {
        $personalData = json_decode($metadata['personal_data'], true);
        if (!$personalData) return;

        $member = new MemberModel();
        $member->tstamp = time();
        $member->dateAdded = time();
        $member->firstname = $personalData['firstname'] ?? '';
        $member->lastname = $personalData['lastname'] ?? '';
        $member->email = $personalData['email'] ?? '';
        $member->username = $personalData['username'] ?? '';
        $member->password = password_hash(base64_decode($personalData['password'] ?? ''), PASSWORD_DEFAULT);
        $member->street = $personalData['street'] ?? '';
        $member->postal = $personalData['postal'] ?? '';
        $member->city = $personalData['city'] ?? '';
        $member->country = $personalData['country'] ?? 'DE';
        $member->phone = $personalData['phone'] ?? '';
        $member->company = $personalData['company'] ?? '';
        $member->login = '1';
        $member->disable = '';
        $member->start = '';
        $member->stop = '';

        $member->save();
    }

    private function createDownloadToken(array $metadata): void
    {
        $productData = json_decode($metadata['product_data'], true);
        $personalData = json_decode($metadata['personal_data'], true);
        
        if (!$productData || !$personalData) return;

        $now = time();
        $expiryDays = intval($productData['download_expires'] ?? 7);
        $downloadLimit = intval($productData['download_limit'] ?? 3);

        $this->db->insert('tl_download_tokens', [
            'tstamp' => $now,
            'token' => $productData['download_token'],
            'file_id' => StringUtil::uuidToBin($productData['file_id']),
            'expires' => $now + ($expiryDays * 86400),
            'download_limit' => $downloadLimit,
            'download_count' => 0,
            'order_id' => $metadata['order_id'] ?? '',
            'customer_email' => $personalData['email'] ?? ''
        ]);
    }

    private function sendOrderConfirmation(array $metadata): void
    {
        try {
            $personalData = json_decode($metadata['personal_data'] ?? '{}', true);
            $productData = json_decode($metadata['product_data'] ?? '{}', true);
            
            if (empty($personalData['email'])) {
                $this->logger->error('Keine E-Mail-Adresse für Bestellbestätigung gefunden', [
                    'metadata' => $metadata
                ]);
                return;
            }

            // E-Mail Template laden
            $templateName = $metadata['email_template'] ?: 'email_order_confirmation';
            
            // Prüfen ob die Template-Datei existiert
            $templateFile = $this->projectDir . '/templates/' . $templateName . '.html5';
            if (!file_exists($templateFile)) {
                $this->logger->warning('E-Mail-Template nicht gefunden, verwende Standard-Template', [
                    'template' => $templateName,
                    'fallback' => 'email_order_confirmation'
                ]);
                $templateName = 'email_order_confirmation';
            }
            
            $template = new FrontendTemplate($templateName);

            // Template-Variablen setzen
            $template->order_id = $metadata['order_id'] ?? uniqid('ORDER_');
            $template->product_name = $metadata['product_title'] ?? $productData['title'] ?? 'Produkt';
            
            // Preis formatieren
            $price = $productData['price'] ?? 0;
            $currency = strtoupper($metadata['currency'] ?? 'EUR');
            $template->product_price = number_format($price / 100, 2, ',', '.') . ' ' . $currency;
            
            $template->customer_name = trim(($personalData['firstname'] ?? '') . ' ' . ($personalData['lastname'] ?? ''));
            $template->customer_email = $personalData['email'];

            // Download-Link generieren wenn nötig
            if (isset($productData['file_sale']) && $productData['file_sale']) {
                try {
                    $downloadLink = $this->generateDownloadLink($productData);
                    if ($downloadLink) {
                        $template->download_link = $downloadLink;
                        
                        // Download-Anweisungen
                        $expiryDays = $productData['download_expires'] ?? 7;
                        $downloadLimit = $productData['download_limit'] ?? 3;
                        
                        $template->download_instructions = sprintf(
                            'Ihr Download-Link ist %d Tage gültig und kann %d mal verwendet werden.',
                            $expiryDays,
                            $downloadLimit
                        );
                    } else {
                        $this->logger->warning('Download-Link konnte nicht generiert werden', [
                            'product_data' => $productData
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Fehler beim Generieren des Download-Links', [
                        'error' => $e->getMessage(),
                        'product_data' => $productData
                    ]);
                }
            }

            // E-Mail senden
            try {
                $email = new Email();
                
                // E-Mail-Absender aus Contao-Konfiguration laden
                $container = System::getContainer();
                $emailFrom = $container->hasParameter('contao.email.from') ? 
                    $container->getParameter('contao.email.from') : 
                    \Contao\Config::get('adminEmail');
                    
                $emailFromName = $container->hasParameter('contao.email.from_name') ? 
                    $container->getParameter('contao.email.from_name') : 
                    \Contao\Config::get('websiteTitle');
                
                if (empty($emailFrom)) {
                    $this->logger->warning('Kein E-Mail-Absender konfiguriert, verwende Default', [
                        'default' => 'noreply@contao.local'
                    ]);
                    $emailFrom = 'noreply@contao.local';
                }
                
                $email->from = $emailFrom;
                $email->fromName = $emailFromName ?: 'Ihre Website';
                $email->subject = 'Ihre Bestellung: ' . $template->product_name;
                
                // HTML-Inhalt generieren
                $html = $template->parse();
                if (empty(trim(strip_tags($html)))) {
                    $this->logger->warning('Leeres E-Mail-Template, generiere Standard-Text', [
                        'template' => $templateName
                    ]);
                    
                    // Fallback-Text generieren
                    $html = $this->generateDefaultEmailContent($template);
                }
                
                $email->html = $html;
                $email->text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
                
                $result = $email->sendTo($personalData['email']);
                
                if ($result) {
                    $this->logger->info('Bestellbestätigung erfolgreich gesendet', [
                        'email' => $personalData['email'],
                        'order_id' => $template->order_id
                    ]);
                } else {
                    $this->logger->error('Bestellbestätigung konnte nicht gesendet werden', [
                        'email' => $personalData['email']
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Senden der Bestellbestätigung', [
                    'error' => $e->getMessage(),
                    'recipient' => $personalData['email'] ?? 'unknown'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Bestellbestätigung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function generateDownloadLink(array $productData): string
    {
        $token = $productData['download_token'] ?? '';
        $fileId = $productData['file_id'] ?? '';
        
        if (!$token || !$fileId) return '';
        
        $baseUrl = System::getContainer()->getParameter('contao.base_url');
        return sprintf(
            '%s/download/%s/%s',
            rtrim($baseUrl, '/'),
            $token,
            StringUtil::binToUuid($fileId)
        );
    }
}
