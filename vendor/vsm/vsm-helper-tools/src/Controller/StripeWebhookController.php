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
                $result = $this->handlePaymentIntentSuccess($paymentIntent);
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
                        
                        $result = $this->handlePaymentIntentSuccess($paymentIntent);
                    } else {
                        $result = ['status' => 'success', 'message' => 'Charge verarbeitet, aber PaymentIntent nicht gefunden'];
                    }
                } else {
                    $result = ['status' => 'success', 'message' => 'Charge ohne PaymentIntent verarbeitet'];
                }
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
    private function handlePaymentIntentSuccess($paymentIntent): array
    {
        $this->logger->info('Payment Intent erfolgreich', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100,
            'currency' => $paymentIntent->currency,
            'has_metadata' => !empty($paymentIntent->metadata) ? 'ja' : 'nein'
        ]);
        
        // Prüfe, ob eine Rechnung existiert oder erstellt werden soll
        // Standard: Rechnung erstellen, wenn nicht explizit deaktiviert
        $shouldCreateInvoice = true;
        
        // Prüfe verschiedene mögliche Metadata-Formate
        if (!empty($paymentIntent->metadata)) {
            if (isset($paymentIntent->metadata->generate_invoice)) {
                $shouldCreateInvoice = filter_var($paymentIntent->metadata->generate_invoice, FILTER_VALIDATE_BOOLEAN);
            } else if (isset($paymentIntent->metadata->create_invoice)) {
                $shouldCreateInvoice = filter_var($paymentIntent->metadata->create_invoice, FILTER_VALIDATE_BOOLEAN);
            } else if (isset($paymentIntent->metadata->invoice)) {
                $shouldCreateInvoice = filter_var($paymentIntent->metadata->invoice, FILTER_VALIDATE_BOOLEAN);
            }
        }
        
        $this->logger->info('Rechnungserstellung: ' . ($shouldCreateInvoice ? 'aktiviert' : 'deaktiviert'));
        
        // Nur fortfahren, wenn eine Rechnung erstellt werden soll
        if ($shouldCreateInvoice) {
            $this->logger->info('Prüfe auf existierende Rechnung oder erstelle eine neue');
            
            // Prüfe, ob eine vorhandene Rechnung existiert
            $invoiceExists = false;
            
            try {
                // Suche zuerst nach kürzlich erstellten Rechnungen für diesen Kunden
                $customerInvoices = \Stripe\Invoice::all([
                    'customer' => $paymentIntent->customer,
                    'limit' => 5,
                    'created' => [
                        'gte' => time() - 3600 // letzte Stunde
                    ]
                ]);
                
                // Prüfe, ob eine der Rechnungen diese Payment Intent in den Metadaten hat
                $invoiceFound = false;
                if (!empty($customerInvoices->data)) {
                    foreach ($customerInvoices->data as $invoice) {
                        if (isset($invoice->metadata->related_to_payment) && 
                            $invoice->metadata->related_to_payment === $paymentIntent->id) {
                            $invoiceExists = true;
                            $this->logger->info('Rechnung basierend auf Metadaten gefunden', [
                                'invoice_id' => $invoice->id,
                                'invoice_number' => $invoice->number
                            ]);
                            $invoiceFound = true;
                            break;
                        }
                    }
                }
                
                // Prüfen, ob in den Metadaten des PaymentIntent eine Rechnungs-ID gespeichert ist
                if (!$invoiceFound && !empty($paymentIntent->metadata->invoice_id)) {
                    $invoiceExists = true;
                    $this->logger->info('Rechnung in PaymentIntent-Metadaten gefunden', [
                        'invoice_id' => $paymentIntent->metadata->invoice_id
                    ]);
                    $invoiceFound = true;
                }
                
                // Wenn keine Rechnung gefunden wurde, erstelle eine
                if (!$invoiceFound && !empty($paymentIntent->customer)) {
                    // Eine Sperre in der Datenbank setzen, um doppelte Rechnungen zu vermeiden
                    $lockKey = 'invoice_creation_' . $paymentIntent->id;
                    $lockValue = uniqid('lock_', true);
                    $isLocked = false;
                    
                    try {
                        // Versuche, einen Lock in der Datenbank zu setzen
                        $this->db->beginTransaction();
                        
                        // Prüfe, ob bereits ein Lock existiert
                        $existingLock = $this->db->fetchAssociative(
                            "SELECT * FROM tl_stripe_locks WHERE lock_key = ?",
                            [$lockKey]
                        );
                        
                        if ($existingLock) {
                            // Wenn der Lock älter als 5 Minuten ist, übernehmen wir ihn
                            $lockTime = $existingLock['lock_time'] ?? 0;
                            if (time() - $lockTime > 300) { // 5 Minuten
                                $this->db->executeStatement(
                                    "UPDATE tl_stripe_locks SET lock_value = ?, lock_time = ? WHERE lock_key = ?",
                                    [$lockValue, time(), $lockKey]
                                );
                                $isLocked = true;
                            } else {
                                $this->logger->info('Rechnung wird bereits von einem anderen Prozess erstellt', [
                                    'lock_key' => $lockKey,
                                    'lock_time' => date('Y-m-d H:i:s', $lockTime)
                                ]);
                                $this->db->commit();
                                return [
                                    'success' => true,
                                    'message' => 'Payment Intent wird bereits verarbeitet',
                                    'payment_intent_id' => $paymentIntent->id
                                ];
                            }
                        } else {
                            // Setze einen neuen Lock
                            $this->db->executeStatement(
                                "INSERT INTO tl_stripe_locks (lock_key, lock_value, lock_time) VALUES (?, ?, ?)",
                                [$lockKey, $lockValue, time()]
                            );
                            $isLocked = true;
                        }
                        
                        $this->db->commit();
                    } catch (\Exception $e) {
                        $this->db->rollBack();
                        $this->logger->error('Fehler beim Setzen des Locks: ' . $e->getMessage());
                        // Wir fahren trotzdem fort, da das nur ein zusätzlicher Schutz ist
                    }
                    
                    // Fahre mit der Rechnungserstellung fort
                    $this->logger->info('Keine Rechnung gefunden, erstelle eine neue', [
                        'is_locked' => $isLocked ? 'ja' : 'nein',
                        'lock_key' => $lockKey
                    ]);
                    
                    // Produktbeschreibung zusammenstellen
                    $description = 'Bestellung: ';
                    if (!empty($paymentIntent->metadata->product_title)) {
                        $description .= $paymentIntent->metadata->product_title;
                    } else {
                        $description .= 'Produkt';
                    }
                    
                    if (!empty($paymentIntent->metadata->product_id)) {
                        $description .= ' (ID: ' . $paymentIntent->metadata->product_id . ')';
                    }
                    
                    // Bereite Stripe für API-Aufrufe vor
                    $stripe = new \Stripe\StripeClient($this->stripeSecretKey);
                    
                    // Logging für Debugging
                    $this->logger->info('PaymentIntent Details für Rechnungserstellung', [
                        'payment_intent_id' => $paymentIntent->id,
                        'amount' => $paymentIntent->amount,
                        'amount_as_decimal' => $paymentIntent->amount/100,
                        'currency' => $paymentIntent->currency,
                        'payment_status' => $paymentIntent->status
                    ]);
                    
                    // Berechne den Betrag für die Rechnung
                    // Prüfe, ob ein Steuersatz in den Metadaten angegeben ist
                    $taxRate = 19.0; // Standardsteuersatz
                    $taxIncluded = true; // Standardmäßig Bruttopreise
                    
                    if (isset($paymentIntent->metadata->tax_rate)) {
                        $taxRate = (float)$paymentIntent->metadata->tax_rate;
                    }
                    
                    if (isset($paymentIntent->metadata->tax_included)) {
                        $taxIncluded = filter_var($paymentIntent->metadata->tax_included, FILTER_VALIDATE_BOOLEAN);
                    }
                    
                    // Betrag für die Rechnung (in Cent)
                    $invoiceAmount = $paymentIntent->amount;
                    
                    // Detailliertes Logging des Betrags
                    $this->logger->info('Betragsberechnung für Rechnung', [
                        'original_amount' => $paymentIntent->amount,
                        'amount_decimal' => $paymentIntent->amount / 100,
                        'tax_rate' => $taxRate,
                        'tax_included' => $taxIncluded ? 'ja' : 'nein',
                        'invoice_amount' => $invoiceAmount,
                        'invoice_amount_decimal' => $invoiceAmount / 100
                    ]);
                    
                    try {
                        // Neue Methode zur Rechnungserstellung ohne InvoiceItems
                        $this->logger->info('Verwende neue direkte Methode zur Rechnungserstellung', [
                            'amount' => $invoiceAmount,
                            'currency' => $paymentIntent->currency
                        ]);
                        
                        // 1. Erstelle ein Produkt
                        $product = $stripe->products->create([
                            'name' => $description,
                            'metadata' => [
                                'created_by' => 'webhook_handler',
                                'related_to_payment' => $paymentIntent->id
                            ]
                        ]);
                        
                        // 2. Erstelle einen Preis für das Produkt
                        $price = $stripe->prices->create([
                            'product' => $product->id,
                            'unit_amount' => $invoiceAmount,
                            'currency' => $paymentIntent->currency,
                        ]);
                        
                        $this->logger->info('Preis erstellt', [
                            'price_id' => $price->id,
                            'unit_amount' => $price->unit_amount,
                            'currency' => $price->currency
                        ]);
                        
                        // 3. Erstelle eine Rechnung direkt mit einem Einzelposten
                        $invoice = $stripe->invoices->create([
                            'customer' => $paymentIntent->customer,
                            'collection_method' => 'charge_automatically',
                            'description' => 'Rechnung für ' . $description,
                            'metadata' => [
                                'created_by' => 'direct_invoice_creation',
                                'related_to_payment' => $paymentIntent->id,
                                'amount' => $invoiceAmount
                            ]
                        ]);
                        
                        // 4. Füge eine Rechnungsposition hinzu
                        $invoiceItem = $stripe->invoiceItems->create([
                            'customer' => $paymentIntent->customer,
                            'price' => $price->id,
                            'invoice' => $invoice->id
                        ]);
                        
                        $this->logger->info('Rechnungsposition hinzugefügt', [
                            'invoice_item_id' => $invoiceItem->id,
                            'price_id' => $price->id,
                            'invoice_id' => $invoice->id
                        ]);
                        
                        // 5. Rechnung finalisieren
                        $finalizedInvoice = $stripe->invoices->finalizeInvoice($invoice->id);
                        
                        $this->logger->info('Rechnung finalisiert mit neuem Verfahren', [
                            'invoice_id' => $finalizedInvoice->id,
                            'invoice_number' => $finalizedInvoice->number,
                            'invoice_total' => $finalizedInvoice->total,
                            'invoice_subtotal' => $finalizedInvoice->subtotal
                        ]);
                        
                        // 6. Markiere die Rechnung als bezahlt
                        $paidInvoice = $stripe->invoices->pay($finalizedInvoice->id, [
                            'paid_out_of_band' => true
                        ]);
                        
                        $this->logger->info('Rechnung als bezahlt markiert', [
                            'invoice_id' => $paidInvoice->id,
                            'status' => $paidInvoice->status,
                            'total' => $paidInvoice->total,
                            'total_decimal' => $paidInvoice->total / 100
                        ]);
                        
                        // 5. PaymentIntent mit der Rechnungs-ID aktualisieren
                        $metadataUpdate = [
                            'invoice_id' => $paidInvoice->id,
                        ];
                        
                        // Wenn die Rechnung eine Nummer hat, auch diese speichern
                        if (!empty($paidInvoice->number)) {
                            $metadataUpdate['invoice_number'] = $paidInvoice->number;
                        }
                        
                        // Speichere den tatsächlichen Rechnungsbetrag
                        $metadataUpdate['invoice_amount'] = $paidInvoice->total;
                        $metadataUpdate['invoice_amount_decimal'] = $paidInvoice->total / 100;
                        
                        // Überprüfung des Rechnungsbetrags
                        $this->logger->info('Rechnungsbetrag vor dem Aktualisieren des PaymentIntent', [
                            'invoice_id' => $paidInvoice->id,
                            'total' => $paidInvoice->total,
                            'total_decimal' => $paidInvoice->total / 100,
                            'original_amount' => $invoiceAmount,
                            'original_amount_decimal' => $invoiceAmount / 100
                        ]);
                        
                        // Wenn der Rechnungsbetrag 0 ist, setze ihn manuell auf den ursprünglichen Betrag
                        if ($paidInvoice->total == 0) {
                            $this->logger->warning('Rechnungsbetrag ist 0, setze manuell auf ursprünglichen Betrag', [
                                'invoice_id' => $paidInvoice->id, 
                                'original_amount' => $invoiceAmount
                            ]);
                            
                            $metadataUpdate['invoice_amount'] = $invoiceAmount;
                            $metadataUpdate['invoice_amount_decimal'] = $invoiceAmount / 100;
                            $metadataUpdate['manual_amount_correction'] = 'true';
                        }
                        
                        $stripe->paymentIntents->update($paymentIntent->id, [
                            'metadata' => $metadataUpdate
                        ]);
                        
                        $this->logger->info('Rechnung erfolgreich erstellt und mit PaymentIntent verknüpft', [
                            'invoice_id' => $paidInvoice->id,
                            'invoice_number' => $paidInvoice->number ?? 'nicht verfügbar',
                            'payment_intent_id' => $paymentIntent->id,
                            'amount' => $invoiceAmount / 100,
                            'invoice_total' => $paidInvoice->total / 100,
                            'invoice_subtotal' => $paidInvoice->subtotal / 100,
                        ]);
                        
                        $invoiceExists = true;
                    
                    } catch (\Exception $e) {
                        $this->logger->error('Fehler bei der direkten Rechnungserstellung: ' . $e->getMessage(), [
                            'error_code' => $e->getCode(),
                            'payment_intent_id' => $paymentIntent->id,
                            'amount' => $invoiceAmount
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler bei der Rechnungserstellung im Webhook: ' . $e->getMessage(), [
                    'payment_intent' => $paymentIntent->id,
                    'error_code' => $e->getCode()
                ]);
            }
        }
        
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
                    $this->logger->info('Benutzeraccount wird erstellt', [
                        'email' => $personalData['email']
                    ]);
                    
                    try {
                        // Benutzer erstellen über den UserCreationService
                        $metadata = [
                            'personalData' => $paymentIntent->metadata->personal_data,
                            'productData' => $paymentIntent->metadata->product_data ?? '{}'
                        ];
                        
                        $this->userService->createContaoUser($metadata);
                    } catch (\Exception $e) {
                        $this->logger->error('Fehler beim Erstellen des Benutzers: ' . $e->getMessage());
                    }
                }
                
                // Prüfe, ob Dateidownload aktiviert ist
                $downloadLink = null;
                if (!empty($productData['file_sale']) && !empty($productData['file_path'])) {
                    try {
                        $downloadToken = $productData['download_token'] ?? $this->generateDownloadToken();
                        $downloadExpires = $productData['download_expires'] ?? 7;
                        $downloadLimit = $productData['download_limit'] ?? 3;
                        
                        // Download-Link generieren
                        $downloadInfo = $this->downloadService->generateDownloadLinkSimple(
                            $productData['file_path'],
                            $downloadExpires,
                            $downloadLimit
                        );
                        
                        $downloadLink = $downloadInfo['url'];
                        
                        // Download-Token in der Datenbank speichern
                        if (!empty($downloadInfo['token'])) {
                            $this->downloadService->createDownloadToken(
                                $downloadInfo['token'],
                                $productData['file_path'],
                                $downloadInfo['expires'],
                                $downloadInfo['limit'],
                            $paymentIntent->id, 
                            $personalData['email']
                        );
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Fehler bei der Verarbeitung des Datei-Downloads: ' . $e->getMessage());
                    }
                }
                
                // E-Mail senden über den EmailService
                $this->emailService->sendPaymentNotification(
                    $personalData,
                    $paymentIntent,
                    $emailTemplate,
                    $productData,
                    $downloadLink
                );
            } else {
                $this->logger->error('Keine E-Mail-Adresse in den persönlichen Daten für Payment Intent: ' . $paymentIntent->id);
            }
        } else {
            // Fallback für alte Payment Intents ohne Metadaten
            $this->logger->warning('Payment Intent ohne Metadaten, verwende Fallback-Verarbeitung', [
                'payment_intent_id' => $paymentIntent->id
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Payment Intent erfolgreich verarbeitet',
            'payment_intent_id' => $paymentIntent->id
        ];
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
}
