<?php

namespace Vsm\VsmHelperTools\Helper;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Hilfsklasse zum Laden und Rendern von E-Mail-Templates
 */
class EmailHelper
{
    /**
     * Lädt ein E-Mail-Template aus dem templates/emails/ Verzeichnis
     * 
     * @param string $templateName Der Name des Templates ohne .html5 Endung
     * @return string Der Inhalt des Templates oder leerer String im Fehlerfall
     */
    public static function loadTemplate($templateName)
    {
        if (empty($templateName)) {
            self::logError('Leerer Template-Name übergeben');
            return '';
        }
        
        try {
            // In Contao 5.x den Projektpfad über Symfony ermitteln
            $projectDir = \Contao\System::getContainer()->getParameter('kernel.project_dir');
            
            // Mögliche Pfade für Templates definieren
            $paths = [
                $projectDir . '/templates/emails/' . $templateName . '.html5',
                $projectDir . '/templates/' . $templateName . '.html5',
                $projectDir . '/vendor/vsm/vsm-helper-tools/templates/emails/' . $templateName . '.html5',
                $projectDir . '/vendor/vsm/vsm-helper-tools/templates/' . $templateName . '.html5'
            ];
            
            foreach ($paths as $path) {
                self::logInfo('Suche Template in: ' . $path);
                
                if (file_exists($path)) {
                    $content = file_get_contents($path);
                    
                    if ($content) {
                        self::logInfo('Template gefunden und geladen: ' . $path);
                        return $content;
                    } else {
                        self::logWarning('Template existiert, konnte aber nicht gelesen werden: ' . $path);
                    }
                }
            }
            
            // Falls kein Template gefunden wurde, generieren wir einen Notfall-Inhalt
            self::logError('Kein Template in den angegebenen Pfaden gefunden für: ' . $templateName);
            
            // Ein einfaches Standard-Template zurückgeben
            return self::getDefaultTemplate();
            
        } catch (\Exception $e) {
            // Fehler beim Laden des Templates
            self::logError('Fehler beim Laden des E-Mail-Templates: ' . $e->getMessage(), ['exception' => $e]);
            // Direktes Error-Logging für Debugging
            error_log('EMAIL_HELPER_ERROR: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            
            return self::getDefaultTemplate();
        }
    }
    
    /**
     * Generiert ein Standard-E-Mail-Template, falls kein spezifisches gefunden wurde
     */
    private static function getDefaultTemplate()
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333;">
            <h2 style="color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">Ihre Bestellung wurde erfolgreich abgeschlossen</h2>
            
            <p>Sehr geehrte/r {{customer_name}},</p>
            
            <p>vielen Dank für Ihren Einkauf. Ihre Bestellung mit der Nummer <strong>{{order_id}}</strong> wurde erfolgreich abgeschlossen.</p>
            
            <div style="background-color: #f9f9f9; border: 1px solid #eee; padding: 15px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #2c3e50;">Bestellübersicht</h3>
                <p>
                    <strong>Produkt:</strong> {{product_name}}<br>
                    <strong>Preis:</strong> {{product_price}}
                </p>
            </div>
            
            {{download_instructions}}
            
            <p style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                Mit freundlichen Grüßen<br>
                Ihr Team
            </p>
        </div>';
    }
    
    /**
     * Ersetzt Platzhalter im Template mit tatsächlichen Werten
     * 
     * @param string $template Der Template-Inhalt
     * @param array $data Assoziatives Array mit Platzhaltern und deren Werten
     * @return string Das gerenderte Template
     */
    public static function renderTemplate($template, array $data)
    {
        if (empty($template)) {
            return '';
        }
        
        // Platzhalter ersetzen ({{placeholder}} durch tatsächlichen Wert)
        foreach ($data as $placeholder => $value) {
            $template = str_replace('{{' . $placeholder . '}}', $value, $template);
        }
        
        // Entferne spezielle Bereiche wie Download-Anweisungen, wenn keine Download-Daten vorhanden sind
        if (!isset($data['download_link']) || empty($data['download_link'])) {
            $template = str_replace('{{download_instructions}}', '', $template);
        }
        
        // Entferne alle verbleibenden unersetzten Platzhalter
        $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);
        
        return $template;
    }
    
    /**
     * Lädt und rendert ein E-Mail-Template mit den angegebenen Daten
     * 
     * @param string $templateName Der Name des Templates
     * @param array $data Die Daten zum Ersetzen der Platzhalter
     * @return string Das gerenderte Template
     */
    public static function getRenderedEmail($templateName, array $data)
    {
        $template = self::loadTemplate($templateName);
        return self::renderTemplate($template, $data);
    }
    
    /**
     * Schreibt eine Fehler-Nachricht ins Log
     */
    private static function logError($message, array $context = [])
    {
        if (class_exists('\Contao\System') && \Contao\System::getContainer()->has('monolog.logger.contao')) {
            \Contao\System::getContainer()->get('monolog.logger.contao')->error(
                $message, 
                $context
            );
        }
        // Direktes Logging für Debugging
        error_log('[EmailHelper ERROR] ' . $message);
    }
    
    /**
     * Schreibt eine Info-Nachricht ins Log
     */
    private static function logInfo($message, array $context = [])
    {
        if (class_exists('\Contao\System') && \Contao\System::getContainer()->has('monolog.logger.contao')) {
            \Contao\System::getContainer()->get('monolog.logger.contao')->info(
                $message, 
                $context
            );
        }
    }
    
    /**
     * Schreibt eine Warnung ins Log
     */
    private static function logWarning($message, array $context = [])
    {
        if (class_exists('\Contao\System') && \Contao\System::getContainer()->has('monolog.logger.contao')) {
            \Contao\System::getContainer()->get('monolog.logger.contao')->warning(
                $message, 
                $context
            );
        }
        // Direktes Logging für Debugging
        error_log('[EmailHelper WARNING] ' . $message);
    }
} 