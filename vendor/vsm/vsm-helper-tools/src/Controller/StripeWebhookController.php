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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vsm\VsmHelperTools\Service\Stripe\StripePaymentService;
use Vsm\VsmHelperTools\Service\User\UserCreationService;
use Vsm\VsmHelperTools\Service\Payment\PaymentSessionManager;
use Vsm\VsmHelperTools\Service\Email\EmailService;
use Vsm\VsmHelperTools\Service\Download\DownloadLinkGenerator;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[Route('/stripe', defaults: ['_scope' => 'frontend'])]
#[AutoconfigureTag('controller.service_arguments')]
class StripeWebhookController extends AbstractController
{
    private ContaoFramework $framework;
    private LoggerInterface $logger;
    private string $webhookSecret;
    private string $stripeSecretKey;
    private StripePaymentService $stripeService;
    private UserCreationService $userService;
    private PaymentSessionManager $paymentProcessorService;
    private EmailService $emailService;
    private DownloadLinkGenerator $downloadService;
    private string $projectDir;
    private Connection $db;

    public function __construct(
        ContaoFramework $framework,
        LoggerInterface $logger,
        string $webhookSecret,
        string $stripeSecretKey,
        StripePaymentService $stripeService,
        UserCreationService $userService,
        PaymentSessionManager $paymentProcessorService,
        EmailService $emailService,
        DownloadLinkGenerator $downloadService,
        string $projectDir,
        Connection $db
    ) {
        $this->framework = $framework;
        $this->logger = $logger;
        $this->webhookSecret = $webhookSecret;
        $this->stripeSecretKey = $stripeSecretKey;
        $this->stripeService = $stripeService;
        $this->userService = $userService;
        $this->paymentProcessorService = $paymentProcessorService;
        $this->emailService = $emailService;
        $this->downloadService = $downloadService;
        $this->projectDir = $projectDir;
        $this->db = $db;
        
        // Webhook-Secret für den StripePaymentService setzen
        $this->stripeService->setWebhookSecret($webhookSecret);
        
        // Stellen Sie sicher, dass die Sperrentabelle existiert
        $this->ensureLockTableExists();
    }

    /**
     * Stellt sicher, dass die Tabelle für Sperren existiert
     */
    private function ensureLockTableExists(): void
    {
        try {
            // Prüfe, ob die Tabelle bereits existiert
            $tableExists = $this->db->executeQuery("SHOW TABLES LIKE 'tl_stripe_locks'")->rowCount() > 0;
            
            if (!$tableExists) {
                // Tabelle erstellen, wenn sie nicht existiert
                $this->db->executeStatement("
                    CREATE TABLE IF NOT EXISTS tl_stripe_locks (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        lock_key VARCHAR(255) NOT NULL,
                        lock_value VARCHAR(255) NOT NULL,
                        lock_time INT NOT NULL,
                        UNIQUE KEY lock_key (lock_key)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                
                $this->logger->info('Tabelle tl_stripe_locks wurde erstellt');
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen der Sperrentabelle: ' . $e->getMessage());
        }
    }

    /**
     * Überprüft, ob ein Benutzer mit den angegebenen Daten bereits existiert
     */
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
            
            // Signatur validieren und Event konstruieren über den StripePaymentService
            $event = $this->stripeService->validateWebhookSignature(
                $payload, 
                $request->headers->get('Stripe-Signature')
            );
            
            if (!$event) {
                $this->logger->error('Ungültige Webhook-Signatur');
                return new Response('Webhook signature verification failed', 400);
            }
            
            $this->logger->info('Webhook-Event validiert', [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'api_version' => $event->api_version
            ]);
            
            // Event-Typ überprüfen
            if ($event->type === 'payment_intent.succeeded') {
                $paymentIntent = $event->data->object;
                $result = $this->handleSuccessfulPayment($paymentIntent);
            } else if ($event->type === 'charge.updated' || $event->type === 'charge.succeeded') {
                // Bei charge.updated/succeeded prüfen, ob eine Rechnung erstellt werden soll
                $charge = $event->data->object;
                
                // Prüfen, ob ein payment_intent existiert
                if (!empty($charge->payment_intent)) {
                    // PaymentIntent abrufen
                    $paymentIntent = \Stripe\PaymentIntent::retrieve($charge->payment_intent);
                    
                    // Wenn der PaymentIntent existiert, wie einen erfolgreichen PaymentIntent behandeln
                    if ($paymentIntent) {
                        $this->logger->info('Charge-Event mit PaymentIntent erkannt', [
                            'event_type' => $event->type,
                            'charge_id' => $charge->id,
                            'payment_intent_id' => $paymentIntent->id
                        ]);
                        
                        $result = $this->handleSuccessfulPayment($paymentIntent);
                    } else {
                        $result = ['status' => 'success', 'message' => 'Charge verarbeitet, aber PaymentIntent nicht gefunden'];
                    }
                } else {
                    $result = ['status' => 'success', 'message' => 'Charge ohne PaymentIntent verarbeitet'];
                }
            } else if ($event->type === 'payment_intent.canceled') {
                // Alle payment_intent.canceled Events ignorieren, da es keine gewollten Stornierungen gibt
                $paymentIntent = $event->data->object;
                
                $this->logger->info('Ignoriere ungewollte Stornierungstransaktion', [
                    'payment_intent_id' => $paymentIntent->id,
                    'cancellation_reason' => $paymentIntent->cancellation_reason ?? 'unbekannt'
                ]);
                
                $result = ['status' => 'success', 'message' => 'Stornierungstransaktion ignoriert'];
            } else {
                // Andere Event-Typen an den StripePaymentService delegieren
                $result = $this->stripeService->processWebhookEvent($event);
            }
            
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
     * Verarbeitet einen erfolgreichen Payment Intent
     */
    private function handleSuccessfulPayment($paymentIntent): array
    {
        $this->logger->info('Erfolgreich abgeschlossene Zahlung empfangen', [
            'payment_intent_id' => $paymentIntent->id,
            'customer_id' => $paymentIntent->customer ?? 'nicht gesetzt',
            'amount' => ($paymentIntent->amount / 100) . ' ' . strtoupper($paymentIntent->currency),
            'status' => $paymentIntent->status
        ]);
        
        try {
            // Zahlung in der Datenbank verarbeiten, falls nötig
            // Hier könnten weitere Verarbeitungslogiken eingefügt werden
            
            // Bei Abos oder anderen speziellen Produkten könnten hier zusätzliche Schritte erfolgen
            // Stripe erstellt bei Checkout Sessions automatisch Rechnungen, wenn invoice_creation aktiviert ist
            
            return [
                'success' => true,
                'message' => 'Zahlung erfolgreich verarbeitet',
                'payment_intent_id' => $paymentIntent->id
            ];
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Zahlungsverarbeitung: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Fehler bei der Zahlungsverarbeitung',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generiert einen sicheren Token für Downloads
     */
    private function generateDownloadToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Erstellt einen Payment Intent für die Stripe JavaScript API
     */
    #[Route('/create-payment-intent', name: 'stripe_create_payment_intent', methods: ['POST', 'OPTIONS'])]
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

            // Prüfe den Content-Type
            if (!$request->headers->contains('Content-Type', 'application/json')) {
                throw new \Exception('Ungültiger Content-Type. Erwartet wird application/json');
            }

            // Daten aus dem Request-Body lesen
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \Exception('Ungültige JSON-Daten: ' . json_last_error_msg());
            }

            // Payment Intent über den StripePaymentService erstellen
            $paymentIntent = $this->stripeService->createPaymentIntent($data);

            return new JsonResponse([
                'clientSecret' => $paymentIntent->client_secret,
                'id' => $paymentIntent->id
            ], 200, $headers);

        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen des PaymentIntent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => [
                    'message' => 'Fehler beim Erstellen des PaymentIntent: ' . $e->getMessage()
                ]
            ], 400, $headers);
        }
    }

    /**
     * Ruft den Preis eines Produkts von Stripe ab oder erstellt ihn
     */
    #[Route('/get-price', name: 'stripe_get_price', methods: ['POST'])]
    public function getPrice(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ungültiges JSON: ' . json_last_error_msg());
            }

            // Validierung der Eingabedaten
            if (empty($data['amount']) || !is_numeric($data['amount'])) {
                throw new \Exception('Ungültiger Betrag');
            }
            if (empty($data['currency']) || !in_array(strtolower($data['currency']), ['eur', 'usd', 'gbp'])) {
                throw new \Exception('Ungültige Währung');
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
            $this->logger->error('Fehler bei der Preiserstellung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Prüft, ob ein Payment Intent einem Abonnement zugeordnet ist
     */
    private function isSubscriptionRelatedPayment($paymentIntent): bool
    {
        // 1. Direkte Prüfung auf is_subscription in Metadaten
        if (!empty($paymentIntent->metadata)) {
            if (isset($paymentIntent->metadata->is_subscription) && 
                filter_var($paymentIntent->metadata->is_subscription, FILTER_VALIDATE_BOOLEAN)) {
                $this->logger->info('Abonnement erkannt durch is_subscription Metadaten', [
                    'payment_intent_id' => $paymentIntent->id
                ]);
                return true;
            }
            
            // ENTFERNT: Die alleinige Existenz von stripe_product_id bedeutet nicht, dass es ein Abo ist
            // Stattdessen prüfen, ob explizit beide Werte gesetzt sind:
            if (!empty($paymentIntent->metadata->stripe_product_id) && 
                !empty($paymentIntent->metadata->is_subscription)) {
                $this->logger->info('Mögliches Abonnement erkannt durch stripe_product_id und is_subscription Flag', [
                    'payment_intent_id' => $paymentIntent->id,
                    'stripe_product_id' => $paymentIntent->metadata->stripe_product_id
                ]);
                return true;
            }
        }
        
        // 3. Prüfung auf invoice.subscription in Metadaten (wird bei Abo-Rechnungen gesetzt)
        try {
            // Prüfe, ob es bereits Rechnungen mit subscription gibt
            $customerInvoices = \Stripe\Invoice::all([
                'customer' => $paymentIntent->customer,
                'limit' => 5
            ]);
            
            foreach ($customerInvoices->data as $invoice) {
                if (!empty($invoice->subscription)) {
                    $this->logger->info('Abonnement erkannt durch bestehende Rechnung mit Abo-Referenz', [
                        'payment_intent_id' => $paymentIntent->id,
                        'invoice_id' => $invoice->id,
                        'subscription_id' => $invoice->subscription
                    ]);
                    return true;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Fehler bei der Prüfung auf Abonnement-Rechnungen: ' . $e->getMessage());
        }
        
        // Keine Anzeichen für ein Abonnement gefunden
        $this->logger->info('Keine Anzeichen für ein Abonnement gefunden', [
            'payment_intent_id' => $paymentIntent->id
        ]);
        return false;
    }
}
