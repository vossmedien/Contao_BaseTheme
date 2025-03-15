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

namespace Vsm\VsmHelperTools\Service;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Email;
use Contao\NotificationCenter\Model\Notification;
use Psr\Log\LoggerInterface;
use Vsm\VsmHelperTools\Service\MemberService;
use Vsm\VsmHelperTools\Service\StripePaymentService;
use Vsm\VsmHelperTools\Service\FileDownloadService;

class PaymentProcessorService
{
    /**
     * PaymentProcessorService constructor
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
        private readonly StripePaymentService $stripeService,
        private readonly MemberService $memberService,
        private readonly FileDownloadService $fileDownloadService,
        private readonly string $projectDir
    ) {
        $this->framework->initialize();
    }

    /**
     * Verarbeitet ein Stripe Event vom Webhook
     */
    public function processStripeEvent($event): array
    {
        $result = [
            'success' => false,
            'message' => 'Unbekanntes Event',
            'event_type' => $event->type,
        ];

        switch ($event->type) {
            case 'payment_intent.succeeded':
                return $this->processPaymentIntent($event->data->object);
                
            case 'payment_intent.canceled':
                return $this->processPaymentIntentCanceled($event->data->object);
                
            case 'checkout.session.completed':
                return $this->processCheckoutSession($event->data->object);
                
            // Weitere Event-Typen könnten hier hinzugefügt werden
            
            default:
                $this->logger->info('Unbekanntes Stripe Event: ' . $event->type);
                break;
        }

        return $result;
    }

    /**
     * Verarbeitet ein erfolgreiches PaymentIntent-Event
     */
    public function processPaymentIntent($payment): array
    {
        $result = [
            'success' => true,
            'message' => 'Zahlung erfolgreich verarbeitet',
            'payment_id' => $payment->id,
        ];

        $this->logger->info('Verarbeite PaymentIntent: ' . $payment->id, [
            'payment_id' => $payment->id,
            'has_metadata' => isset($payment->metadata) ? 'ja' : 'nein'
        ]);

        // Kundendaten extrahieren
        $customerEmail = null;
        if (!empty($payment->charges->data[0]->billing_details->email)) {
            $customerEmail = $payment->charges->data[0]->billing_details->email;
        } else if (!empty($payment->customer)) {
            // Versuche den Kunden abzurufen, um die E-Mail zu bekommen
            try {
                $customer = \Stripe\Customer::retrieve($payment->customer);
                $customerEmail = $customer->email;
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Abrufen des Kunden: ' . $e->getMessage());
            }
        }
        
        if (!$customerEmail) {
            $this->logger->error('Keine E-Mail-Adresse gefunden für PaymentIntent: ' . $payment->id);
            return [
                'success' => false,
                'message' => 'Keine E-Mail-Adresse gefunden',
                'payment_id' => $payment->id,
            ];
        }

        // Personal- und Produktdaten aus den Metadaten extrahieren
        $personalData = [];
        if (!empty($payment->metadata->personalData)) {
            $personalData = json_decode($payment->metadata->personalData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warning('Ungültiges JSON in personalData', [
                    'error' => json_last_error_msg(),
                    'personalData' => $payment->metadata->personalData
                ]);
                $personalData = [];
            }
        }
        
        $productMetadata = [];
        if (!empty($payment->metadata->productData)) {
            $productMetadata = json_decode($payment->metadata->productData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warning('Ungültiges JSON in productData', [
                    'error' => json_last_error_msg(),
                    'productData' => $payment->metadata->productData
                ]);
                $productMetadata = [];
            }
        }
        
        $this->logger->info('Extrahierte Metadaten', [
            'personalData_keys' => array_keys($personalData),
            'productMetadata_keys' => array_keys($productMetadata),
            'product_title' => $payment->metadata->productTitle ?? 'nicht gefunden'
        ]);

        // Mitgliedschaft verarbeiten, wenn vorhanden
        if (!empty($payment->metadata->createUser) && $payment->metadata->createUser === 'true') {
            try {
                $memberId = $this->memberService->createOrUpdateMember($productMetadata, $customerEmail);
                $result['member_id'] = $memberId;
                $this->logger->info('Mitglied erstellt/aktualisiert: ' . $memberId);
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Erstellen/Aktualisieren des Mitglieds: ' . $e->getMessage());
            }
        }

        // Überprüfen, ob ein Download verfügbar ist
        $downloadLink = null;
        if (!empty($productMetadata['file_sale']) && !empty($productMetadata['file_path'])) {
            try {
                $this->logger->info('Bereite Datei-Download vor', [
                    'file_path' => $productMetadata['file_path'],
                    'download_token' => $productMetadata['download_token'] ?? 'fehlt',
                    'download_expires' => $productMetadata['download_expires'] ?? 7
                ]);
                
                $downloadLink = $this->processFileDownload($productMetadata, $payment->id, $customerEmail);
                $result['download_link'] = $downloadLink;
                $this->logger->info('Download-Link erstellt: ' . $downloadLink);
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Erstellen des Download-Links: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'file_path' => $productMetadata['file_path'] ?? 'nicht gesetzt',
                    'token' => $productMetadata['download_token'] ?? 'nicht gesetzt'
                ]);
            }
        } else {
            $this->logger->info('Kein Datei-Download konfiguriert oder Datei-Pfad fehlt', [
                'file_sale' => $productMetadata['file_sale'] ?? false,
                'file_path' => $productMetadata['file_path'] ?? 'nicht gesetzt'
            ]);
        }

        // Eine Rechnung für den Kauf erstellen
        try {
            $invoice = $this->stripeService->createInvoiceForPayment($payment);
            $result['invoice_id'] = $invoice->id;
            $this->logger->info('Rechnung erstellt: ' . $invoice->id);
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen der Rechnung: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Benachrichtigungen über Contao NotificationCenter senden
        try {
            // Preis-Daten extrahieren und korrekt formatieren
            $amount = $payment->amount / 100;
            $formattedAmount = number_format($amount, 2, ',', '.');
            $currency = strtoupper($payment->currency);
            
            // Download-Ablaufdatum berechnen
            $expiryTimestamp = time() + 60*60*24*intval($productMetadata['download_expires'] ?? 7);
            $validUntil = date('d.m.Y', $expiryTimestamp);
            
            // E-Mail-Daten vorbereiten
            $emailData = [
                'firstName' => strval($personalData['firstname'] ?? ''),
                'lastName' => strval($personalData['lastname'] ?? ''),
                'productTitle' => strval($payment->metadata->productTitle ?? $productMetadata['title'] ?? 'Unbekannt'),
                'username' => strval($personalData['username'] ?? ''),
                'validUntil' => $validUntil,
                'purchaseDate' => date('d.m.Y'),
                'company' => strval($personalData['company'] ?? ''),
                'phone' => strval($personalData['phone'] ?? ''),
                'productId' => strval($payment->metadata->productId ?? $productMetadata['id'] ?? ''),
                'paymentId' => $payment->id,
                'amount' => $formattedAmount,
                'currency' => $currency,
                'street' => strval($personalData['street'] ?? ''),
                'postal' => strval($personalData['postal'] ?? ''),
                'city' => strval($personalData['city'] ?? ''),
                'country' => strval($personalData['country'] ?? 'DE'),
                'email' => $customerEmail
            ];
            
            // Wenn ein Download-Link vorhanden ist, fügen wir ihn den E-Mail-Daten hinzu
            if ($downloadLink) {
                $emailData['downloadLink'] = $downloadLink;
                $emailData['downloadLinkProductXyz'] = $downloadLink; // Für Kompatibilität mit alten Templates
            }
            
            // E-Mail-Template-ID aus den Metadaten extrahieren
            $emailTemplateId = $payment->metadata->emailTemplate ?? null;
            
            if ($emailTemplateId) {
                $this->sendContaoNotification(intval($emailTemplateId), $emailData);
                $this->logger->info('Contao-Benachrichtigung an Kunden gesendet', [
                    'template_id' => $emailTemplateId,
                    'customer_email' => $customerEmail
                ]);
            } else {
                // Fallback: Standard-E-Mail senden
                $this->sendStandardEmail($customerEmail, $emailData);
                $this->logger->info('Standard-E-Mail an Kunden gesendet', [
                    'customer_email' => $customerEmail
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der E-Mail-Benachrichtigung: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * Verarbeitet einen Datei-Download und erstellt einen Download-Link
     */
    public function processFileDownload(array $fileData, string $paymentId, string $customerEmail): ?string
    {
        try {
            // Validiere die erforderlichen Daten
            if (empty($fileData['file_path']) || empty($fileData['download_token'])) {
                throw new \InvalidArgumentException('Fehlende Datei-Informationen');
            }

            // Standardwerte für Download-Limits
            $downloadExpires = intval($fileData['download_expires'] ?? 7); // 7 Tage Standard
            $downloadLimit = intval($fileData['download_limit'] ?? 3); // 3 Downloads Standard

            // Erstelle einen Download-Eintrag
            $token = $this->fileDownloadService->createDownloadEntry(
                $fileData['file_path'],
                $fileData['download_token'],
                $downloadExpires,
                $downloadLimit,
                $paymentId,
                $customerEmail
            );

            if (!$token) {
                throw new \RuntimeException('Fehler beim Erstellen des Download-Eintrags');
            }

            // Generiere den Download-Link
            return $this->fileDownloadService->generateDownloadLink($token);

        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Verarbeitung des Datei-Downloads: ' . $e->getMessage(), [
                'file_path' => $fileData['file_path'] ?? 'nicht gesetzt',
                'token' => $fileData['download_token'] ?? 'nicht gesetzt',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Sendet eine Benachrichtigung über das Contao NotificationCenter
     */
    public function sendContaoNotification(int $notificationId, array $data): bool
    {
        try {
            $this->framework->initialize();
            
            // Der richtige Service-Name ist 'terminal42.notification_center'
            $notificationCenter = \Contao\System::getContainer()->get('terminal42.notification_center');
            
            if (!$notificationCenter) {
                throw new \Exception('Der Notification Center Service konnte nicht gefunden werden');
            }
            
            $this->logger->info('Sende Benachrichtigung via Notification Center', [
                'notification_id' => $notificationId,
                'token_keys' => array_keys($data)
            ]);
            
            // Tokens standardisieren (alle Werte als Strings, leere Werte als leere Strings)
            $tokens = [];
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $tokens[$key] = json_encode($value);
                } elseif ($value === null) {
                    $tokens[$key] = '';
                } elseif (!is_string($value)) {
                    $tokens[$key] = (string)$value;
                } else {
                    $tokens[$key] = $value;
                }
            }
            
            // Benachrichtigung senden via NotificationCenter-Service
            $receipts = $notificationCenter->sendNotification($notificationId, $tokens);
            
            // Ergebnis prüfen
            $successful = false;
            $errors = [];
            
            foreach ($receipts as $receipt) {
                if ($receipt->isSuccessful()) {
                    $successful = true;
                    $this->logger->info('Benachrichtigung erfolgreich gesendet via Gateway: ' . $receipt->getGatewayName());
                } else {
                    $errors[] = $receipt->getErrorMessage();
                    $this->logger->error('Fehler beim Senden der Benachrichtigung: ' . $receipt->getErrorMessage());
                }
            }
            
            if (!$successful) {
                $this->logger->error('Benachrichtigung konnte nicht gesendet werden', [
                    'errors' => $errors
                ]);
                return false;
            }
            
            $this->logger->info('Benachrichtigung erfolgreich versendet');
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der Contao-Benachrichtigung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'notification_id' => $notificationId
            ]);
            return false;
        }
    }
    
    /**
     * Sendet eine Standard-E-Mail bei erfolgreicher Zahlung
     */
    protected function sendStandardEmail(string $to, array $data): bool 
    {
        try {
            $this->framework->initialize();
            
            $email = new Email();
            $email->from = \Contao\Config::get('adminEmail');
            $email->fromName = \Contao\Config::get('websiteTitle') ?: 'Ihre Website';
            $email->subject = 'Ihre Bestellung wurde erfolgreich abgeschlossen';
            
            // HTML E-Mail erstellen
            $html = sprintf(
                '<h2>Vielen Dank für Ihre Bestellung!</h2>
                <p>Sehr geehrte(r) %s %s,</p>
                <p>Ihre Bestellung wurde erfolgreich abgeschlossen.</p>
                <p><strong>Produkt:</strong> %s</p>
                <p><strong>Betrag:</strong> %s %s</p>
                <p><strong>Bestellnummer:</strong> %s</p>',
                $data['firstName'],
                $data['lastName'],
                $data['productTitle'],
                $data['amount'],
                $data['currency'],
                $data['paymentId']
            );
            
            // Download-Link, falls vorhanden
            if (!empty($data['downloadLink'])) {
                $html .= sprintf(
                    '<p><strong>Ihr Download-Link:</strong> <a href="%s">Hier klicken zum Herunterladen</a></p>
                    <p>Dieser Link ist gültig bis zum %s.</p>',
                    $data['downloadLink'],
                    $data['validUntil']
                );
            }
            
            $html .= '<p>Mit freundlichen Grüßen<br>Ihr Team</p>';
            
            $email->html = $html;
            $email->text = strip_tags($html);
            
            $email->sendTo($to);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der Standard-E-Mail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'to_email' => $to
            ]);
            return false;
        }
    }

    /**
     * Verarbeitet einen abgebrochenen PaymentIntent
     */
    public function processPaymentIntentCanceled($payment, array $data = []): array
    {
        $this->logger->info('Verarbeite stornierten PaymentIntent', [
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'cancellation_reason' => $payment->cancellation_reason ?? 'unbekannt',
            'amount' => $payment->amount ?? 0
        ]);
        
        $result = [
            'success' => true,
            'message' => 'PaymentIntent wurde storniert',
            'payment_id' => $payment->id
        ];
        
        try {
            $invoices = $this->stripeService->getInvoicesForPaymentIntent($payment->id);
            $invoiceCount = count($invoices);
            
            $this->logger->info("$invoiceCount Rechnungen für PaymentIntent gefunden", [
                'payment_id' => $payment->id,
                'invoice_count' => $invoiceCount
            ]);
            
            if (empty($invoices)) {
                return $result;
            }
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($invoices as $invoice) {
                $this->logger->info('Storniere Rechnung für stornierten PaymentIntent', [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'invoice_status' => $invoice->status,
                    'invoice_total' => $invoice->total ?? 0
                ]);
                
                try {
                    // Die direkte voidInvoice-Methode verwenden
                    $updatedInvoice = $this->stripeService->voidInvoice($invoice->id);
                    $successCount++;
                    
                    $this->logger->info('Rechnung erfolgreich storniert', [
                        'invoice_id' => $invoice->id,
                        'new_status' => $updatedInvoice->status,
                        'old_status' => $invoice->status
                    ]);
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $failCount++;
                    
                    $this->logger->error('Stripe API Fehler beim Stornieren der Rechnung', [
                        'error' => $e->getMessage(),
                        'code' => $e->getHttpStatus() ?? 'unbekannt',
                        'stripe_code' => $e->getStripeCode() ?? 'unbekannt',
                        'request_id' => $e->getRequestId() ?? 'unbekannt',
                        'invoice_id' => $invoice->id,
                        'payment_intent_id' => $payment->id
                    ]);
                } catch (\Exception $e) {
                    $failCount++;
                    
                    $this->logger->error('Allgemeiner Fehler beim Stornieren der Rechnung', [
                        'error' => $e->getMessage(),
                        'error_class' => get_class($e),
                        'invoice_id' => $invoice->id,
                        'payment_intent_id' => $payment->id
                    ]);
                }
            }
            
            $result['invoice_summary'] = [
                'total' => $invoiceCount,
                'success' => $successCount,
                'failed' => $failCount
            ];
            
            $this->logger->info('Rechnungsstornierung abgeschlossen', $result['invoice_summary']);
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logger->error('Stripe API Fehler beim Abrufen der Rechnungen', [
                'error' => $e->getMessage(),
                'code' => $e->getHttpStatus() ?? 'unbekannt',
                'stripe_code' => $e->getStripeCode() ?? 'unbekannt',
                'request_id' => $e->getRequestId() ?? 'unbekannt',
                'payment_intent_id' => $payment->id
            ]);
            
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->error('Allgemeiner Fehler beim Abrufen oder Stornieren der Rechnungen', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'payment_intent_id' => $payment->id
            ]);
            
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Verarbeitet eine abgeschlossene Checkout-Session (für Stripe Checkout)
     */
    public function processCheckoutSession($session): array
    {
        $this->logger->info('Checkout-Session abgeschlossen', [
            'session_id' => $session->id,
            'payment_status' => $session->payment_status
        ]);
        
        $result = [
            'success' => true,
            'message' => 'Checkout Session verarbeitet',
            'session_id' => $session->id
        ];
        
        // Hier könnten weitere Verarbeitungsschritte für Checkout Sessions hinzugefügt werden
        
        return $result;
    }

    /**
     * Erstellt oder aktualisiert einen Benutzer auf Basis der Personendaten
     * 
     * @param array $personalData Die persönlichen Daten des Kunden
     * @param int $memberGroup Optional die Mitgliedergruppen-ID
     * @return int|null ID des erstellten/geänderten Mitglieds oder null bei Fehler
     */
    public function createUser(array $personalData, int $memberGroup = null): ?int
    {
        if (empty($personalData['email'])) {
            $this->logger->error('Keine E-Mail-Adresse für Benutzer-Erstellung angegeben');
            return null;
        }
        
        try {
            // Erforderliche Daten für die Mitglied-Erstellung sammeln
            $userData = [
                'email' => $personalData['email'],
                'username' => $personalData['username'] ?? $personalData['email'],
                'password' => $personalData['password'] ?? uniqid('pw_', true),
                'firstname' => $personalData['firstname'] ?? '',
                'lastname' => $personalData['lastname'] ?? '',
                'street' => $personalData['street'] ?? '',
                'postal' => $personalData['postal'] ?? '',
                'city' => $personalData['city'] ?? '',
                'country' => $personalData['country'] ?? 'DE',
                'phone' => $personalData['phone'] ?? '',
                'company' => $personalData['company'] ?? ''
            ];
            
            // Optional Geburtsdatum hinzufügen
            if (!empty($personalData['birthday'])) {
                $userData['dateOfBirth'] = $personalData['birthday'];
            }
            
            // Mitglied erstellen oder aktualisieren
            $memberId = $this->memberService->createOrUpdateMember(
                $userData, 
                $memberGroup
            );
            
            $this->logger->info('Mitglied erstellt/aktualisiert', [
                'member_id' => $memberId,
                'email' => $userData['email']
            ]);
            
            return $memberId;
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Benutzer-Erstellung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
} 