<?php

namespace Vsm\VsmStripeConnect\Service\Email;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;
use Contao\StringUtil;
use Contao\MemberModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Email as ContaoEmail;
use Contao\Config;
use Contao\FrontendTemplate;
use Contao\System;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private Environment $twig;
    private string $projectDir;
    private ?ContaoFramework $framework;
    
    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        Environment $twig,
        string $projectDir,
        ?ContaoFramework $framework = null
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->twig = $twig;
        $this->projectDir = $projectDir;
        $this->framework = $framework;
    }
    
    /**
     * Sendet Bestätigungs-E-Mails nach einer erfolgreichen Zahlung
     */
    public function sendPaymentConfirmation(array $sessionData): bool
    {
        // Prüfen, ob E-Mails bereits gesendet wurden, um doppelte E-Mails zu vermeiden
        if ($sessionData['emails_sent'] ?? false) {
            $this->logger->info('E-Mails für Session ' . $sessionData['session_id'] . ' wurden bereits gesendet');
            return true;
        }
        
        $customerData = $sessionData['customer_data'];
        $productData = $sessionData['product_data'];
        
        // Debug-Informationen über die verfügbaren Session-Daten
        $this->logger->info('Session-Daten vor E-Mail-Versand', [
            'session_id' => $sessionData['session_id'] ?? 'nicht verfügbar',
            'hat_customer_data' => !empty($customerData) ? 'ja' : 'nein',
            'hat_product_data' => !empty($productData) ? 'ja' : 'nein',
            'hat_payment_data' => isset($sessionData['payment_data']) ? 'ja' : 'nein',
            'username' => $customerData['username'] ?? 'nicht verfügbar',
            'mitgliedschaft_duration' => $productData['subscription_duration'] ?? 'nicht verfügbar',
            'hat_invoice' => isset($sessionData['payment_data']['invoice_id']) ? 'ja' : 'nein'
        ]);
        
        // Wenn Zahlungsdaten vorhanden, Rechnungsinformationen prüfen
        if (isset($sessionData['payment_data'])) {
            $this->logger->info('Zahlungsdaten-Details', [
                'payment_id' => $sessionData['payment_data']['payment_id'] ?? 'nicht verfügbar',
                'invoice_id' => $sessionData['payment_data']['invoice_id'] ?? 'nicht verfügbar',
                'invoice_url' => $sessionData['payment_data']['invoice_url'] ?? 'nicht verfügbar',
                'invoice_status' => $sessionData['payment_data']['invoice_status'] ?? 'nicht verfügbar',
            ]);
            
            // Prüfen ob create_invoice in Produktdaten gesetzt ist
            $this->logger->info('Rechnungserstellung in Produktdaten', [
                'create_invoice' => $productData['create_invoice'] ?? 'nicht verfügbar', 
                'data-create-invoice' => $productData['data-create-invoice'] ?? 'nicht verfügbar',
                'createInvoice' => $productData['createInvoice'] ?? 'nicht verfügbar',
            ]);
        }
        
        // Daten für die Templates vorbereiten
        $templateData = $this->prepareTemplateData($sessionData);
        
        // Nach Vorbereitung überprüfen, welche wichtigen Daten im Template verfügbar sind
        $this->logger->info('Template-Daten nach Vorbereitung', [
            'hat_username' => isset($templateData['customer']['username']) ? 'ja' : 'nein',
            'username' => $templateData['customer']['username'] ?? 'nicht verfügbar',
            'hat_subscription_duration' => isset($templateData['order']['duration']) ? 'ja' : 'nein',
            'duration' => $templateData['order']['duration'] ?? 'nicht verfügbar',
            'valid_until' => $templateData['order']['valid_until'] ?? 'nicht verfügbar',
            'hat_invoice' => $templateData['has_invoice'] ? 'ja' : 'nein',
            'invoice_url' => $templateData['invoice']['invoice_url'] ?? 'nicht verfügbar',
        ]);
        
        $success = true;
        
        // Kunden-E-Mail senden
        if (isset($customerData['email']) && !empty($customerData['email'])) {
            try {
                $subject = $productData['email_subject'] ?? 'Ihre Bestellung';
                
                // Template-Name normalisieren - sicherstellen, dass wir ohne Präfix arbeiten
                $template = $productData['user_template'] ?? 'mail_customer_payment_confirmation';
                
                // Entferne "emails/" Präfix, falls vorhanden
                if (strpos($template, 'emails/') === 0) {
                    $template = substr($template, 7);
                }
                
                $this->logger->info('Sende Bestätigungs-Email an Kunden', [
                    'email' => $customerData['email'],
                    'template' => $template,
                    'template_normalisiert' => $template
                ]);
                
                $sent = $this->sendEmail(
                $customerData['email'],
                    $subject,
                    $template,
                    $templateData
                );
                
                if (!$sent) {
                    $this->logger->error('Fehler beim Senden der Kunden-Email');
                    $success = false;
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Senden der Kunden-Email: ' . $e->getMessage());
                $success = false;
            }
        }
        
        // Admin-E-Mail senden
        if (isset($productData['admin_email']) && !empty($productData['admin_email'])) {
            try {
                $subject = $productData['admin_email_subject'] ?? 'Neue Bestellung';
                
                // Template-Name normalisieren
                $template = $productData['admin_template'] ?? 'mail_admin_payment_notification';
                
                // Entferne "emails/" Präfix, falls vorhanden
                if (strpos($template, 'emails/') === 0) {
                    $template = substr($template, 7);
                }
                
                $this->logger->info('Sende Benachrichtigungs-Email an Admin', [
                    'email' => $productData['admin_email'],
                    'template' => $template,
                    'template_normalisiert' => $template
                ]);
                
                $sent = $this->sendEmail(
                $productData['admin_email'],
                    $subject,
                    $template,
                    $templateData
                );
                
                if (!$sent) {
                    $this->logger->error('Fehler beim Senden der Admin-Email');
                    $success = false;
                }
            } catch (\Exception $e) {
                $this->logger->error('Fehler beim Senden der Admin-Email: ' . $e->getMessage());
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Sendet eine E-Mail-Benachrichtigung für eine erfolgreiche Zahlung (für Webhook-Verarbeitung)
     */
    public function sendPaymentNotification(array $personalData, $paymentIntent, $emailTemplate, array $productData, $downloadLink = null): bool
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
            if ($this->framework) {
                $this->framework->initialize();
                $config = Config::all();
                
                // Absender ermitteln - mit Fallback für Fehler
                $emailFrom = $config['adminEmail'] ?? 'shop@contao5.vossmedien.de';
                $emailFromName = $config['websiteTitle'] ?? 'VossMedian Online-Shop';
            } else {
                $emailFrom = 'shop@contao5.vossmedien.de';
                $emailFromName = 'VossMedian Online-Shop';
            }
            
            $emailSubject = 'Ihre Bestellung wurde erfolgreich abgeschlossen';
            
            $this->logger->info('E-Mail-Versandversuch', [
                'to' => $personalData['email'],
                'from' => $emailFrom,
                'subject' => $emailSubject,
                'template_id' => $emailTemplate,
                'has_download' => $downloadLink ? 'ja' : 'nein'
            ]);
            
            // E-Mail senden
            return $this->sendEmail(
                $personalData['email'],
                $emailSubject,
                $emailTemplate ?: 'stripe_payment_confirmation',
                $emailData,
                $emailFrom,
                $emailFromName
            );
            
        } catch (\Exception $e) {
            $this->logger->error('Kritischer Fehler bei der E-Mail-Benachrichtigung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Sendet eine Registrierungs-E-Mail an einen neuen Benutzer
     */
    public function sendRegistrationEmail(MemberModel $member): bool
    {
        try {
            if (!$this->framework) {
                throw new \Exception('Contao Framework ist nicht verfügbar');
            }
            
            $this->framework->initialize();
            
            // Standard-Werte für E-Mail direkt verwenden
            $emailFrom = 'noreply@vossmedien.de';
            $websiteTitle = 'Vossmedien';
            
            try {
                // Versuchen wir, die Werte aus dem Config-Adapter zu holen
                $configAdapter = $this->framework->getAdapter(\Contao\Config::class);
                $fromEmail = $configAdapter->get('adminEmail');
                $title = $configAdapter->get('websiteTitle');
                
                if (!empty($fromEmail)) {
                    $emailFrom = $fromEmail;
                }
                
                if (!empty($title)) {
                    $websiteTitle = $title;
                }
            } catch (\Exception $e) {
                // Bei Fehler Standard-Werte beibehalten
                $this->logger->warning('Fehler beim Laden der E-Mail-Konfiguration: ' . $e->getMessage());
            }
            
            $email = new ContaoEmail();
            $email->from = $emailFrom;
            $email->fromName = $websiteTitle;
            $email->subject = 'Ihre Registrierung wurde erfolgreich abgeschlossen';
            
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
            
            $this->logger->info('Registrierungs-E-Mail wird gesendet', [
                'to' => $member->email,
                'from' => $email->from
            ]);
            
            $email->sendTo($member->email);
            
            $this->logger->info('Registrierungs-E-Mail erfolgreich gesendet', [
                'to' => $member->email
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der Registrierungs-E-Mail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Sendet eine Bestellbestätigung mit oder ohne Download-Link
     */
    public function sendOrderConfirmation(array $metadata): bool
    {
        try {
            $personalData = json_decode($metadata['personal_data'] ?? '{}', true);
            $productData = json_decode($metadata['product_data'] ?? '{}', true);
            
            if (empty($personalData['email'])) {
                $this->logger->error('Keine E-Mail-Adresse für Bestellbestätigung gefunden', [
                    'metadata' => $metadata
                ]);
                return false;
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
            
            // Daten für das Template vorbereiten
            $templateData = [
                'order_id' => $metadata['order_id'] ?? uniqid('ORDER_'),
                'product_name' => $metadata['product_title'] ?? $productData['title'] ?? 'Produkt',
                'customer_name' => trim(($personalData['firstname'] ?? '') . ' ' . ($personalData['lastname'] ?? '')),
                'customer_email' => $personalData['email']
            ];
            
            // Preis formatieren
            $price = $productData['price'] ?? 0;
            $currency = strtoupper($metadata['currency'] ?? 'EUR');
            $templateData['product_price'] = number_format($price / 100, 2, ',', '.') . ' ' . $currency;
            
            // Download-Link hinzufügen, wenn vorhanden
            if (isset($productData['download_link'])) {
                $templateData['download_link'] = $productData['download_link'];
                $templateData['download_expires'] = $productData['download_expires'] ?? 7;
                $templateData['download_limit'] = $productData['download_limit'] ?? 3;
            }
            
            // E-Mail Absender aus Contao-Konfiguration laden
            $emailFrom = 'noreply@contao.local';
            $emailFromName = 'Ihre Website';
            
            if ($this->framework) {
                $this->framework->initialize();
                $container = System::getContainer();
                
                $emailFrom = $container->hasParameter('contao.email.from') ? 
                    $container->getParameter('contao.email.from') : 
                    Config::get('adminEmail');
                    
                $emailFromName = $container->hasParameter('contao.email.from_name') ? 
                    $container->getParameter('contao.email.from_name') : 
                    Config::get('websiteTitle');
            }
            
            // E-Mail senden
            $sent = $this->sendEmail(
                $personalData['email'],
                'Ihre Bestellung: ' . $templateData['product_name'],
                $templateName,
                $templateData,
                $emailFrom,
                $emailFromName
            );
            
            if ($sent) {
                $this->logger->info('Bestellbestätigung erfolgreich gesendet', [
                    'email' => $personalData['email'],
                    'order_id' => $templateData['order_id']
                ]);
            } else {
                $this->logger->error('Bestellbestätigung konnte nicht gesendet werden', [
                    'email' => $personalData['email']
                ]);
            }
            
            return $sent;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Bestellbestätigung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Bereitet Daten für ein E-Mail-Template vor
     */
    private function prepareTemplateData(array $sessionData): array
    {
        $customerData = $sessionData['customer_data'];
        $productData = $sessionData['product_data'];
        $paymentData = $sessionData['payment_data'] ?? [];
        
        // Log der verfügbaren Daten für die Fehlerbehebung
        $this->logger->debug('Sessiondaten für E-Mail-Template vorbereiten', [
            'session_id' => $sessionData['session_id'],
            'has_download_url' => isset($sessionData['download_url']),
            'has_download_token' => isset($sessionData['download_token']),
            'session_keys' => array_keys($sessionData),
        ]);
        
        // Standard-Template-Variablen
        $templateData = [
            'order_id' => $sessionData['session_id'],
            'order_date' => date('d.m.Y H:i', $sessionData['created_at']),
            'order_status' => $sessionData['status'],
            
            // Kundendaten
            'customer' => $customerData,
            'customer_name' => trim(($customerData['firstname'] ?? '') . ' ' . ($customerData['lastname'] ?? '')),
            'customer_email' => $customerData['email'] ?? '',
            'customer_address' => $this->formatAddress($customerData),
            
            // Produktdaten
            'product' => $productData,
            'product_name' => $productData['title'] ?? 'Produkt',
            'product_description' => $productData['description'] ?? '',
            'product_price' => $this->formatPrice($paymentData['amount'] ?? $productData['price'] ?? 0, $paymentData['currency'] ?? $productData['stripe_currency'] ?? 'EUR'),
            'product_markup' => $sessionData['product_markup'] ?? '',
            'product_button_markup' => $sessionData['product_button_markup'] ?? '',
            
            // Zusätzliche Informationen
            'download_link' => $sessionData['download_url'] ?? null,
            'download_expires' => date('d.m.Y', $sessionData['download_expires'] ?? time() + 60*60*24*7),
            'download_limit' => $sessionData['download_limit'] ?? 3,
            'download_count' => $sessionData['download_count'] ?? 0,
            
            // Zahlungsdaten
            'payment' => $paymentData,
            'payment_method' => $paymentData['payment_method'] ?? 'Kreditkarte',
            'payment_id' => $paymentData['payment_id'] ?? '',
            ];
        
        // Boolean-Flags für bedingte Template-Anzeige - explizit prüfen auf download_url
        $templateData['has_download'] = !empty($sessionData['download_url']);
        
        // Zusätzliche Prüfung für den Fall, dass download_url nicht direkt gesetzt ist
        if (!$templateData['has_download'] && isset($productData['file_sale']) && $productData['file_sale']) {
            // Vermutlich ist ein Download vorgesehen, auch wenn die URL nicht gesetzt ist
            $templateData['has_download'] = true;
            
            // Zusätzliche Prüfung auf data- Präfixe (für HTML-Datenattribute)
            if (isset($productData['data-file-sale']) && $productData['data-file-sale']) {
                $templateData['has_download'] = true;
            }
            
            // Suche nach download_token, falls download_url fehlt
            if (isset($sessionData['download_token']) && !empty($sessionData['download_token'])) {
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'contao5.vossmedien.de';
                $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                
                // Generiere Download-URL basierend auf Token
                $templateData['download_link'] = $scheme . '://' . $host . '/stripe/download/' . $sessionData['download_token'];
                
                $this->logger->info('Download-Link rekonstruiert aus Token', [
                    'token' => $sessionData['download_token'],
                    'url' => $templateData['download_link']
                ]);
            }
        }
        
        $templateData['has_invoice'] = !empty($paymentData['invoice_id']);
        
        // Mitgliedschafts-Daten hinzufügen
        $hasMembership = false;
        $membershipDuration = 0;
        
        // Mitgliedschaftsdauer aus Produktdaten extrahieren
        if (isset($productData['subscription_duration']) && !empty($productData['subscription_duration'])) {
            $membershipDuration = intval($productData['subscription_duration']);
            $hasMembership = true;
        } elseif (isset($productData['duration']) && !empty($productData['duration'])) {
            $membershipDuration = intval($productData['duration']);
            $hasMembership = true;
        } elseif (isset($paymentData['duration']) && !empty($paymentData['duration'])) {
            $membershipDuration = intval($paymentData['duration']);
            $hasMembership = true;
        }
        
        // Ablaufdatum für Mitgliedschaft berechnen, wenn Dauer vorhanden
        $membershipValidUntil = null;
        if ($hasMembership && $membershipDuration > 0) {
            $membershipValidUntil = date('Y-m-d', strtotime('+' . $membershipDuration . ' months'));
            
            // Informationen zum Ablauf und Mitgliedschaft in payment-Daten setzen
            $templateData['payment']['membership_duration'] = $membershipDuration;
            $templateData['payment']['membership_valid_until'] = $membershipValidUntil;
            
            $this->logger->info('Mitgliedschaftsdaten vorbereitet', [
                'duration' => $membershipDuration,
                'valid_until' => $membershipValidUntil
            ]);
        }
        
        // "order" Objekt für bessere Template-Kompatibilität
        $templateData['order'] = [
            'id' => $sessionData['session_id'],
            'product_title' => $productData['title'] ?? 'Produkt',
            'product_id' => $productData['id'] ?? '',
            'price' => $paymentData['amount'] ?? $productData['price'] ?? 0,
            'price_formatted' => $templateData['product_price'],
            'currency' => $paymentData['currency'] ?? $productData['stripe_currency'] ?? 'EUR',
            'status' => $sessionData['status'],
            'created_at' => date('d.m.Y H:i', $sessionData['created_at']),
            'duration' => $membershipDuration, // Mitgliedschaftsdauer hinzufügen
            'valid_until' => $membershipValidUntil // Ablaufdatum hinzufügen
        ];
        
        // Stelle sicher, dass der Benutzername im customer-Objekt enthalten ist
        if (isset($customerData['username']) && !empty($customerData['username'])) {
            $templateData['customer']['username'] = $customerData['username'];
        } elseif (isset($sessionData['user_creation']['username']) && !empty($sessionData['user_creation']['username'])) {
            $templateData['customer']['username'] = $sessionData['user_creation']['username'];
        }
        
        // Download-Informationen als strukturiertes Objekt
        if ($templateData['has_download']) {
            $templateData['download'] = [
                'download_url' => $templateData['download_link'],
                'expires_date' => $templateData['download_expires'],
                'download_limit' => $templateData['download_limit'],
                'download_count' => $templateData['download_count'],
            ];
            
            // Debug-Log
            $this->logger->debug('Download-Informationen für E-Mail vorbereitet', [
                'download_url' => $templateData['download']['download_url'],
                'expires_date' => $templateData['download']['expires_date']
            ]);
        }
        
        // Rechnungsinformationen als strukturiertes Objekt
        if ($templateData['has_invoice']) {
            $templateData['invoice'] = [
                'invoice_id' => $paymentData['invoice_id'] ?? '',
                'invoice_number' => $paymentData['invoice_number'] ?? '',
                'invoice_url' => $paymentData['invoice_url'] ?? '',
                'invoice_pdf' => $paymentData['invoice_pdf'] ?? '',
                'invoice_date' => date('d.m.Y', isset($paymentData['invoice_date']) && is_numeric($paymentData['invoice_date']) ? (int)$paymentData['invoice_date'] : time()),
            ];
        }
        
        // Payment Objekt für Template-Kompatibilität
        $templateData['payment'] = [
            'transaction_id' => $paymentData['payment_id'] ?? $sessionData['session_id'],
            'status' => $paymentData['status'] ?? $sessionData['status'],
            'payment_method' => $paymentData['payment_method'] ?? 'Kreditkarte',
            'payment_id' => $paymentData['payment_id'] ?? '',
            'amount' => $paymentData['amount'] ?? $productData['price'] ?? 0,
            'amount_formatted' => $templateData['product_price'],
            'currency' => $paymentData['currency'] ?? $productData['stripe_currency'] ?? 'EUR',
        ];
        
        // Komplettes Session-Data als Referenz
        $templateData['session_data'] = $sessionData;
        
        $this->logger->debug('Template-Daten vorbereitet', [
            'customer_name' => $templateData['customer_name'],
            'product_name' => $templateData['product_name'],
            'has_download' => $templateData['has_download'] ? 'ja' : 'nein',
            'has_invoice' => $templateData['has_invoice'] ? 'ja' : 'nein',
            'order_keys' => array_keys($templateData['order'])
        ]);
        
        // Mitgliedschaftsinformationen, falls vorhanden
        $templateData['has_username'] = !empty($sessionData['customer_data']['username']);
        $templateData['username'] = $sessionData['customer_data']['username'] ?? '';
        
        // Mitgliedschaftsdauer und Ablaufdatum
        $hasDuration = false;
        $duration = 0;
        $validUntil = null;
        
        // Prüfe verschiedene Quellen für Mitgliedschaftsdaten
        if (isset($paymentData['duration'])) {
            $hasDuration = true;
            $duration = $paymentData['duration'];
            $validUntil = $paymentData['membership_valid_until'] ?? date('Y-m-d', strtotime('+' . $duration . ' months'));
        } 
        else if (isset($productData['subscription_duration'])) {
            $hasDuration = true;
            $duration = intval($productData['subscription_duration']);
            $validUntil = date('Y-m-d', strtotime('+' . $duration . ' months'));
        } 
        else if (isset($productData['duration'])) {
            $hasDuration = true;
            $duration = intval($productData['duration']);
            $validUntil = date('Y-m-d', strtotime('+' . $duration . ' months'));
        }
        
        // Prüfen, ob Mitgliedschaftsinformationen in den Button-Daten zu finden sind
        if (!$hasDuration && isset($productData['data'])) {
            $buttonData = $productData['data'];
            if (is_string($buttonData)) {
                $buttonData = json_decode($buttonData, true);
            }
            
            if (is_array($buttonData)) {
                // Überprüfen von gängigen Schlüsseln für Mitgliedschaftsdauer
                $durationKeys = ['duration', 'subscription_duration', 'membership_duration'];
                foreach ($durationKeys as $key) {
                    if (isset($buttonData[$key]) && $buttonData[$key] > 0) {
                        $hasDuration = true;
                        $duration = intval($buttonData[$key]);
                        $validUntil = date('Y-m-d', strtotime('+' . $duration . ' months'));
                        break;
                    }
                }
            }
        }
        
        // Wenn im Markup explizit "5 Monate" steht, dies verwenden
        if (!$hasDuration && isset($sessionData['product_markup']) && 
            strpos($sessionData['product_markup'], 'Mitgliedschaft: 5 Monate') !== false) {
            $hasDuration = true;
            $duration = 5;
            $validUntil = date('Y-m-d', strtotime('+5 months'));
        }
        
        // Formatiertes Gültigkeitsdatum
        $formattedValidUntil = '';
        if ($validUntil) {
            $date = \DateTime::createFromFormat('Y-m-d', $validUntil);
            if ($date) {
                $formattedValidUntil = $date->format('d.m.Y');
            } else {
                $formattedValidUntil = $validUntil;
            }
        }
        
        $templateData['has_subscription_duration'] = $hasDuration;
        $templateData['duration'] = $duration;
        $templateData['valid_until'] = $formattedValidUntil;
        
        // Umfangreiche Debug-Informationen für die Template-Daten
        $this->logger->info('Template-Daten nach Vorbereitung', [
            'hat_username' => $templateData['has_username'] ? 'ja' : 'nein',
            'username' => $templateData['username'] ?: 'nicht verfügbar',
            'hat_subscription_duration' => $templateData['has_subscription_duration'] ? 'ja' : 'nein',
            'duration' => $templateData['duration'],
            'valid_until' => $templateData['valid_until'] ?: 'nicht verfügbar'
        ]);
        
        // Rechnungsinformationen
        if (!empty($paymentData['invoice_id'])) {
            $templateData['has_invoice'] = true;
            $templateData['invoice_id'] = $paymentData['invoice_id'];
            $templateData['invoice_number'] = $paymentData['invoice_number'] ?? '';
            $templateData['invoice_url'] = $paymentData['invoice_url'] ?? '';
            $templateData['invoice_pdf'] = $paymentData['invoice_pdf'] ?? '';
            $templateData['invoice_date'] = date('d.m.Y', isset($paymentData['invoice_date']) && is_numeric($paymentData['invoice_date']) ? (int)$paymentData['invoice_date'] : time());
            
            $this->logger->info('Rechnungsdaten für E-Mail hinzugefügt', [
                'invoice_id' => $templateData['invoice_id'],
                'invoice_url' => $templateData['invoice_url'] ?: 'nicht verfügbar'
            ]);
        } else {
            $templateData['has_invoice'] = false;
            $this->logger->info('Keine Rechnungsdaten verfügbar für E-Mail');
        }
        
        return $templateData;
    }
    
    /**
     * Formatiert die Adresse für die E-Mail
     */
    private function formatAddress(array $customerData): string
    {
        $address = [];
        
        if (!empty($customerData['company'])) {
            $address[] = $customerData['company'];
        }
        
        if (!empty($customerData['firstname']) || !empty($customerData['lastname'])) {
            $address[] = trim(($customerData['firstname'] ?? '') . ' ' . ($customerData['lastname'] ?? ''));
        }
        
        if (!empty($customerData['street'])) {
            $address[] = $customerData['street'];
        }
        
        if (!empty($customerData['postal']) || !empty($customerData['city'])) {
            $address[] = trim(($customerData['postal'] ?? '') . ' ' . ($customerData['city'] ?? ''));
        }
        
        if (!empty($customerData['country'])) {
            $address[] = $customerData['country'];
        }
        
        return implode('<br>', $address);
    }
    
    /**
     * Formatiert den Preis für die E-Mail
     */
    private function formatPrice($amount, $currency): string
    {
        // Umrechnung von Cent in Euro, falls nötig
        if ($amount > 100 && $amount % 100 === 0) {
            $amount = $amount / 100;
        }
        
        return number_format((float)$amount, 2, ',', '.') . ' ' . strtoupper($currency);
    }
    
    /**
     * Konvertiert Features-String in ein Array
     */
    private function formatFeatures(string $features): array
    {
        $featuresArray = [];
        $lines = explode("\n", $features);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $featuresArray[] = $line;
            }
        }
        
        return $featuresArray;
    }
    
    /**
     * Sendet eine E-Mail mit Contao-Integration
     */
    public function sendEmail(string $to, string $subject, string $template, array $data, string $from = '', string $fromName = ''): bool
    {
        if (empty($from)) {
            $from = 'noreply@' . $_SERVER['HTTP_HOST'];
        }
        
        if (empty($fromName)) {
            $fromName = 'Webseite';
        }
        
        try {
            // Ergänze emails/-Pfad zum Template, wenn nicht vorhanden
            $templateForRendering = $template;
            if (strpos($templateForRendering, 'emails/') !== 0) {
                $templateForRendering = 'emails/' . $templateForRendering;
            }
            
            $this->logger->debug('E-Mail-Versand vorbereitet', [
                'to' => $to,
                'from' => $from,
                'subject' => $subject,
                'original_template' => $template,
                'template_for_rendering' => $templateForRendering
            ]);
            
            // HTML-Body rendern
            $htmlBody = $this->renderEmailTemplate($templateForRendering, $data);
            
            // Wenn wir keinen HTML-Body haben, dann einen Fehler werfen
            if (empty(trim($htmlBody))) {
                throw new \Exception('Leerer E-Mail-Body nach dem Rendern des Templates: ' . $templateForRendering);
            }
            
            // Klartext-Version erzeugen
            $textBody = strip_tags(str_replace(['<br>', '<br />', '<br/>', '</p>'], "\n", $htmlBody));
            
            // E-Mail erstellen und senden
            $email = (new Email())
                ->from(new Address($from, $fromName))
                ->to($to)
                ->subject($subject)
                ->html($htmlBody)
                ->text($textBody);
            
            $this->mailer->send($email);
            
            $this->logger->info('E-Mail erfolgreich gesendet', [
                'to' => $to,
                'subject' => $subject,
                'template' => $templateForRendering
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der E-Mail', [
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject,
                'template' => $templateForRendering ?? $template
            ]);
            
            // Fallback mit Contao Email-Klasse versuchen
            try {
                if ($this->framework) {
                    $this->framework->initialize();
                    
                    $contaoEmail = new ContaoEmail();
                    $contaoEmail->from = $from;
                    $contaoEmail->fromName = $fromName;
                    $contaoEmail->subject = $subject;
                    $contaoEmail->html = $htmlBody;
                    $contaoEmail->text = $textBody;
                    
                    $contaoEmail->sendTo($to);
                    
                    $this->logger->info('E-Mail über Contao-Fallback erfolgreich gesendet', [
                        'to' => $to
                    ]);
                    
                    return true;
                }
            } catch (\Exception $e2) {
                $this->logger->error('Auch Contao-Fallback für E-Mail fehlgeschlagen', [
                    'error' => $e2->getMessage()
                ]);
            }
            
            return false;
        }
    }
    
    /**
     * Rendert ein E-Mail-Template
     */
    private function renderEmailTemplate(string $template, array $data): string
    {
        try {
            // Direkter Debug des Template-Namens und Projektverzeichnisses
            $this->logger->debug('Template-Rendering-Details', [
                'template' => $template,
                'project_dir' => $this->projectDir,
                'template_enthält_emails_präfix' => strpos($template, 'emails/') === 0
            ]);
            
            // Twig-Template-Versuch
            try {
                $twigTemplateName = $template . '.html.twig';
                if ($this->twig->getLoader()->exists($twigTemplateName)) {
                    $this->logger->info('Twig-Template gefunden: ' . $twigTemplateName);
                    return $this->twig->render($twigTemplateName, $data);
                }
            } catch (\Exception $e) {
                // Ignorieren
            }
            
            // Template-Namen normalisieren - Entfernen vom emails/ Präfix falls vorhanden
            $normalizedTemplate = $template;
            if (strpos($normalizedTemplate, 'emails/') === 0) {
                $normalizedTemplate = substr($normalizedTemplate, 7); // Entferne "emails/"
                $this->logger->debug('Normalisiere Template-Name', [
                    'original' => $template,
                    'normalisiert' => $normalizedTemplate
                ]);
            }
            
            // Alle möglichen Pfade ausprobieren
            $templatePaths = [
                // Direkter Pfad wie angegeben
                $this->projectDir . '/templates/' . $template . '.html5',
                
                // Pfad ohne emails-Präfix wenn es eines hat
                $this->projectDir . '/templates/' . $normalizedTemplate . '.html5',
                
                // Standard-Email-Template-Pfad
                $this->projectDir . '/templates/emails/' . $normalizedTemplate . '.html5',
                
                // Alternatives Format (ohne Verzeichnisstruktur)
                $this->projectDir . '/templates/emails/' . basename($normalizedTemplate) . '.html5',
                
                // Alte Pfade für Kompatibilität (alle Variationen)
                $this->projectDir . '/vendor/vsm/vsm-helper-tools/templates/' . $template . '.html5',
                $this->projectDir . '/vendor/vsm/vsm-helper-tools/templates/' . $normalizedTemplate . '.html5',
                $this->projectDir . '/vendor/vsm/vsm-helper-tools/templates/emails/' . $normalizedTemplate . '.html5'
            ];
            
            // Template-Existenz prüfen und Zugriff testen
            foreach ($templatePaths as $path) {
                $this->logger->debug('Prüfe Template-Pfad: ' . $path);
                
                if (file_exists($path)) {
                    $this->logger->info('Template-Datei gefunden: ' . $path);
                    
                    if (is_readable($path)) {
                        $this->logger->info('Template ist lesbar, versuche zu rendern');
                        
                        // Direkter Zugriff testen
                        try {
                            // Template-Inhalt lesen (nur zu Testzwecken)
                            $content = file_get_contents($path);
                            $this->logger->debug('Template-Größe: ' . strlen($content) . ' Bytes');
                            
                            // Contao-Template rendern
                            return $this->renderContaoTemplate($path, $data);
                        } catch (\Exception $ex) {
                            $this->logger->error('Fehler beim Lesen/Rendern des Templates: ' . $ex->getMessage(), [
                                'path' => $path,
                                'error' => $ex->getMessage()
                            ]);
                            // Weiter zum nächsten Pfad
                        }
                    } else {
                        $this->logger->warning('Template-Datei gefunden, aber nicht lesbar: ' . $path);
                    }
                }
            }
            
            // NOTFALL-FALLBACK: Erstelle ein einfaches Standard-Template wenn nichts gefunden wurde
            $this->logger->warning('Kein Template gefunden, erstelle Fallback-Template');
            $fallbackTemplate = $this->createFallbackTemplate($data);
            
            return $fallbackTemplate;
            
        } catch (\Exception $e) {
            $this->logger->error('Kritischer Fehler beim Rendern des E-Mail-Templates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'template' => $template
            ]);
            
            // Sehr einfache Fallback-Nachricht im Fehlerfall
            return '<p style="color: red;">FEHLER: E-Mail-Template ' . htmlspecialchars($template) . ' nicht gefunden! Bitte kontaktieren Sie den Administrator.</p>
                    <p>Technische Details: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    
    /**
     * Erstellt ein einfaches Fallback-Template mit den Bestell-/Produktdaten
     */
    private function createFallbackTemplate(array $data): string
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Bestellbestätigung</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; }
                .footer { background-color: #f8f9fa; padding: 15px; margin-top: 20px; font-size: 12px; }
                .product { margin-bottom: 20px; }
                .download-box { background-color: #e8f4fc; border: 1px solid #b8e0f7; padding: 15px; margin: 20px 0; }
                h1, h2, h3 { color: #2980b9; }
                a { color: #2980b9; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Bestellbestätigung</h1>
                </div>
                
                <p>Vielen Dank für Ihre Bestellung.</p>
                
                <div class="product">
                    <h3>Bestelldetails</h3>
                    <p><strong>Produkt:</strong> ' . htmlspecialchars($data['product_name'] ?? 'Produkt') . '</p>
                    <p><strong>Preis:</strong> ' . htmlspecialchars($data['product_price'] ?? '-') . '</p>
                    <p><strong>Bestellnummer:</strong> ' . htmlspecialchars($data['order_id'] ?? 'Nicht verfügbar') . '</p>
                </div>';
        
        // Download-Informationen hinzufügen, falls vorhanden
        if (!empty($data['download_link'])) {
            $html .= '
                <div class="download-box">
                    <h3>Download-Informationen</h3>
                    <p>Sie können Ihr gekauftes Produkt jetzt herunterladen:</p>
                    <p><a href="' . htmlspecialchars($data['download_link']) . '">Download starten</a></p>
                    <p><strong>Hinweis:</strong> Der Download-Link ist gültig für ' . ($data['download_expires'] ?? '7') . ' Tage und kann maximal ' . ($data['download_limit'] ?? '3') . ' mal verwendet werden.</p>
                </div>';
        }
        
        $html .= '
                <div class="footer">
                    <p>Bei Fragen zu Ihrer Bestellung kontaktieren Sie uns bitte unter ' . ($data['sender_email'] ?? 'info@vossmedien.de') . '.</p>
                    <p>&copy; ' . date('Y') . ' VossMedian - Alle Rechte vorbehalten</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Prüft, ob ein Template existiert
     */
    private function templateExists(string $name): bool
    {
        // Twig-Template prüfen
        try {
            if ($this->twig->getLoader()->exists($name . '.html.twig')) {
            return true;
            }
        } catch (\Exception $e) {
            // Ignorieren
        }
        
        // Verschiedene mögliche Pfade für Contao-Templates prüfen
        $templatePaths = [
            // Standard-Pfad
            $this->projectDir . '/templates/' . $name . '.html5',
            
            // Pfad mit Basisname (ohne Verzeichnisse)
            $this->projectDir . '/templates/' . basename($name) . '.html5',
            
            // Templates im emails/-Unterverzeichnis
            $this->projectDir . '/templates/emails/' . basename($name) . '.html5',
            
            // Alte Pfade für Kompatibilität
            $this->projectDir . '/vendor/vsm/vsm-helper-tools/templates/' . $name . '.html5',
            $this->projectDir . '/vendor/vsm/vsm-helper-tools/templates/emails/' . basename($name) . '.html5'
        ];
        
        foreach ($templatePaths as $path) {
            if (file_exists($path)) {
                $this->logger->info('Template gefunden unter: ' . $path);
                return true;
            }
        }
        
        $this->logger->warning('Template nicht gefunden: ' . $name, [
            'geprüfte_pfade' => $templatePaths
        ]);
        
        return false;
    }
    
    /**
     * Rendert ein Contao-Template
     */
    private function renderContaoTemplate(string $templatePath, array $data): string
    {
        if (!$this->framework) {
            $this->logger->warning('Contao Framework nicht verfügbar, verwende direktes Template-Rendering');
            return $this->renderTemplateDirectly($templatePath, $data);
        }
        
        try {
            $this->framework->initialize();
        
            // FrontendTemplate erstellen
            $templateBaseName = basename($templatePath, '.html5');
            
            // Prüfen, ob wir ein Email-Template haben und ob es sich im Pfad /templates/emails/ befindet
            $isEmailTemplate = strpos($templatePath, '/templates/emails/') !== false;
            
            // Bei Email-Templates den richtigen Pfad konstruieren
            if ($isEmailTemplate) {
                // Extrahiere nur den Dateinamen ohne Pfad und Erweiterung
                $templateName = 'emails/' . $templateBaseName;
                $this->logger->debug('Erkenne Email-Template, verwende Template-Name: ' . $templateName);
            } else {
                $templateName = $templateBaseName;
            }
            
            $this->logger->debug('Erstelle FrontendTemplate für ' . $templateName);
            $template = new FrontendTemplate($templateName);
        
            // Daten in den globalen Scope extrahieren
            $this->extractToGlobalScope($data);
            
            // Zusätzliche Variable für Objektzugriff im Template
            $GLOBALS['template_data'] = (object)$data;
            
            // Daten an das Template übergeben
            foreach ($data as $key => $value) {
                $template->$key = $value;
            }
            
            // Setze das komplette data-Array auch als template_data-Objekt
            $template->template_data = (object)$data;
            
            $parsedContent = $template->parse();
            
            // Prüfen ob wir leeren Inhalt haben
            if (empty(trim($parsedContent))) {
                $this->logger->warning('Template wurde leer gerendert, versuche direktes Rendering als Fallback');
                return $this->renderTemplateDirectly($templatePath, $data);
            }
            
            return $parsedContent;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Rendern des Contao-Templates', [
                'error' => $e->getMessage(),
                'template' => $templatePath
            ]);
        
            // Versuche direktes Rendering als Fallback
            return $this->renderTemplateDirectly($templatePath, $data);
        }
    }
    
    /**
     * Rendert ein Template direkt ohne Contao Framework
     */
    private function renderTemplateDirectly(string $templatePath, array $data): string
    {
        try {
            $this->logger->info('Direktes Template-Rendering für ' . $templatePath);
            
            if (!file_exists($templatePath) || !is_readable($templatePath)) {
                throw new \Exception('Template nicht lesbar: ' . $templatePath);
            }
            
            // Template-Inhalt lesen
            $templateContent = file_get_contents($templatePath);
            
            // Global variable preparation
            // Standarddaten für E-Mail-Templates - diese Variablen werden im template erwartet
            if (!isset($data['order'])) {
                $data['order'] = [];
            }
            if (!isset($data['customer'])) {
                $data['customer'] = [];
            }
            if (!isset($data['payment'])) {
                $data['payment'] = [];
            }
            if (!isset($data['download'])) {
                $data['download'] = [];
            }
            if (!isset($data['invoice'])) {
                $data['invoice'] = [];
            }
            if (!isset($data['has_download'])) {
                $data['has_download'] = false;
            }
            if (!isset($data['has_invoice'])) {
                $data['has_invoice'] = false;
            }
            
            // Konvertiere Array in Objekt für Template-Kompatibilität
            $template_data = (object)$data;
            
            // Alle Variablen aus dem Data-Array extrahieren
            extract($data);
            
            // PHP-Code ausführen, indem wir den Template-Inhalt in einem Output-Buffer auswerten
            ob_start();
            
            // Führe Template-PHP-Code aus
            try {
                // Benutze include_once mit einem temporären File statt eval für bessere Fehlerbehandlung
                $tempFile = tempnam(sys_get_temp_dir(), 'email_tpl_');
                file_put_contents($tempFile, $templateContent);
                
                // Stelle sicher, dass die Rechte stimmen
                chmod($tempFile, 0644);
                
                // Führe das Template aus
                include $tempFile;
                
                // Lösche die temporäre Datei
                unlink($tempFile);
                
                $rendered = ob_get_clean();
                return $rendered;
            } catch (\Throwable $e) {
                ob_end_clean();
                $this->logger->error('PHP-Fehler im Template: ' . $e->getMessage(), [
                    'template' => $templatePath,
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]);
                
                // Fallback auf einfachen Ersatz, wenn PHP-Ausführung fehlschlägt
                return $this->simpleTemplateReplace($templateContent, $data);
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim direkten Rendering', [
                'error' => $e->getMessage(),
                'template' => $templatePath
            ]);
            
            // Im Fehlerfall verwenden wir das Fallback-Template
            return $this->createFallbackTemplate($data);
        }
    }
    
    /**
     * Einfache Template-Ersetzung ohne PHP-Ausführung
     */
    private function simpleTemplateReplace(string $content, array $data): string
    {
        // Einfachen Template-Ersatz durchführen
        $search = [];
        $replace = [];
        
        foreach ($data as $key => $value) {
            // Nur skalare Werte direkt ersetzen
            if (is_scalar($value)) {
                $search[] = '{{' . $key . '}}';
                $replace[] = $value;
                
                // Auch HTML-geschützte Varianten unterstützen
                $search[] = '{{' . $key . '|escape}}';
                $replace[] = htmlspecialchars($value);
            }
        }
        
        // Grundlegende Template-Ersetzung
        $rendered = str_replace($search, $replace, $content);
        
        // PHP-Tags entfernen, da wir sie nicht ausführen
        $rendered = preg_replace('/<\?php.*?\?>/s', '', $rendered);
        
        // Noch nicht ersetzte Variablen entfernen oder markieren
        $rendered = preg_replace('/\{\{[^\}]+\}\}/', '', $rendered);
        
        return $rendered;
    }
    
    /**
     * Extrahiert Variablen in den globalen Scope für Contao-Templates
     */
    private function extractToGlobalScope(array $data): void
    {
        if (!$this->framework) {
            return;
        }
        
        $GLOBALS['TL_DATA'] = $data;
        
        foreach ($data as $key => $value) {
            if (!is_array($value) && !is_object($value)) {
                $GLOBALS[$key] = $value;
            }
        }
    }
} 