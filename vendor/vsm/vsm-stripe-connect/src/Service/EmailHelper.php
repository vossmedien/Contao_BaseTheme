<?php

namespace Vsm\VsmStripeConnect\Service;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendTemplate;
use Contao\Email;
use Contao\Config;
use Contao\System;
use Psr\Log\LoggerInterface;

class EmailHelper
{
    private static string $projectDir;
    private static LoggerInterface $logger;
    private static ContaoFramework $framework;

    /**
     * Initialisiert die statischen Eigenschaften
     */
    public static function initialize(string $projectDir, LoggerInterface $logger, ContaoFramework $framework)
    {
        self::$projectDir = $projectDir;
        self::$logger = $logger;
        self::$framework = $framework;
        
        // Framework initialisieren, falls es noch nicht initialisiert ist
        if (!$framework->isInitialized()) {
            $framework->initialize();
        }
    }

    /**
     * Rendert ein E-Mail-Template mit den gegebenen Daten
     *
     * @param string $templateName Name des E-Mail-Templates (ohne .html5)
     * @param array $data Daten, die dem Template zur Verfügung gestellt werden
     * @return string|null Gerenderte E-Mail oder null im Fehlerfall
     */
    public static function getRenderedEmail(string $templateName, array $data): ?string
    {
        try {
            // Prüfen, ob das Framework initialisiert wurde
            if (!isset(self::$framework) || !self::$framework->isInitialized()) {
                throw new \RuntimeException('Contao Framework nicht initialisiert. Bitte EmailHelper::initialize() aufrufen.');
            }

            // Überprüfen, ob die Template-Datei existiert
            $templateFile = self::$projectDir . '/templates/' . $templateName . '.html5';
            if (!file_exists($templateFile)) {
                if (isset(self::$logger)) {
                    self::$logger->warning('E-Mail-Template nicht gefunden', [
                        'template' => $templateName,
                        'file' => $templateFile
                    ]);
                }
                return null;
            }

            // Template erstellen und rendern
            $template = new FrontendTemplate($templateName);
            
            // Daten an das Template übergeben
            foreach ($data as $key => $value) {
                $template->$key = $value;
            }
            
            // Template rendern
            $html = $template->parse();
            
            // Prüfen, ob das gerenderte Template leer ist
            if (empty(trim(strip_tags($html)))) {
                if (isset(self::$logger)) {
                    self::$logger->warning('Gerenderte E-Mail ist leer', [
                        'template' => $templateName
                    ]);
                }
                return null;
            }
            
            return $html;
        } catch (\Exception $e) {
            if (isset(self::$logger)) {
                self::$logger->error('Fehler beim Rendern des E-Mail-Templates', [
                    'template' => $templateName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            return null;
        }
    }
    
    /**
     * Sendet eine E-Mail mit dem angegebenen Template und Daten
     *
     * @param string $to E-Mail-Empfänger
     * @param string $subject Betreff
     * @param string $templateName Name des E-Mail-Templates (ohne .html5)
     * @param array $data Daten für das Template
     * @return bool Erfolg des Sendevorgangs
     */
    public static function sendTemplatedEmail(string $to, string $subject, string $templateName, array $data): bool
    {
        try {
            // Framework initialisieren
            if (!isset(self::$framework) || !self::$framework->isInitialized()) {
                throw new \RuntimeException('Contao Framework nicht initialisiert. Bitte EmailHelper::initialize() aufrufen.');
            }

            // Rendern des Templates
            $html = self::getRenderedEmail($templateName, $data);
            if (!$html) {
                // Erstelle einen Fallback-Inhalt
                $html = self::generateDefaultEmailContent($data);
            }
            
            // E-Mail Konfiguration
            $configAdapter = self::$framework->getAdapter(Config::class);
            $emailFrom = $configAdapter->get('adminEmail');
            $emailFromName = $configAdapter->get('websiteTitle');
            
            if (empty($emailFrom)) {
                if (isset(self::$logger)) {
                    self::$logger->warning('Kein E-Mail-Absender konfiguriert', [
                        'default' => 'noreply@example.com'
                    ]);
                }
                $emailFrom = 'noreply@example.com';
            }
            
            // E-Mail erstellen und senden
            $email = new Email();
            $email->from = $emailFrom;
            $email->fromName = $emailFromName ?: 'Ihre Website';
            $email->subject = $subject;
            $email->html = $html;
            $email->text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
            
            $success = $email->sendTo($to);
            
            if ($success && isset(self::$logger)) {
                self::$logger->info('E-Mail erfolgreich gesendet', [
                    'to' => $to,
                    'template' => $templateName
                ]);
            } elseif (isset(self::$logger)) {
                self::$logger->error('E-Mail konnte nicht gesendet werden', [
                    'to' => $to,
                    'template' => $templateName
                ]);
            }
            
            return $success;
        } catch (\Exception $e) {
            if (isset(self::$logger)) {
                self::$logger->error('Fehler beim Senden der E-Mail', [
                    'to' => $to,
                    'template' => $templateName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            return false;
        }
    }
    
    /**
     * Generiert einen Standard-E-Mail-Inhalt für den Fall, dass kein Template existiert
     */
    private static function generateDefaultEmailContent(array $data): string
    {
        $customerName = $data['customer_name'] ?? ($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? '');
        $orderId = $data['order_id'] ?? 'Ihre Bestellung';
        $productName = $data['product_name'] ?? 'Produkt';
        $productPrice = $data['product_price'] ?? '';
        $downloadInstructions = $data['download_instructions'] ?? '';
        
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">Vielen Dank für Ihre Bestellung!</h2>
            <p>Sehr geehrte(r) ' . ($customerName ?: 'Kunde') . ',</p>
            <p>vielen Dank für Ihren Einkauf. Ihre Bestellung <strong>' . $orderId . '</strong> wurde erfolgreich abgeschlossen.</p>
            
            <div style="background-color: #f7f7f7; border: 1px solid #ddd; padding: 15px; margin: 20px 0;">
                <h3 style="margin-top: 0;">Bestellübersicht</h3>
                <p><strong>Produkt:</strong> ' . $productName . 
                ($productPrice ? '<br><strong>Preis:</strong> ' . $productPrice : '') . '</p>
            </div>
            
            ' . $downloadInstructions . '
            
            <p>Bei Fragen zu Ihrer Bestellung stehen wir Ihnen gerne zur Verfügung.</p>
            <p>Mit freundlichen Grüßen<br>Ihr Team</p>
        </div>';
    }
} 