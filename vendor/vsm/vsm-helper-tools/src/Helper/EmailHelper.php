<?php

declare(strict_types=1);

namespace Vsm\VsmHelperTools\Helper;

use Contao\System;

/**
 * Email Helper
 * 
 * Hilfsklasse zum Laden und Rendern von E-Mail-Templates mit verschiedenen
 * Fallback-Pfaden und Template-Verarbeitung.
 */
class EmailHelper
{
    // Container Cache für Performance
    private static $container = null;

    /**
     * Optimierter Container-Zugriff
     */
    private static function getContainer()
    {
        return self::$container ??= System::getContainer();
    }

    /**
     * Lädt ein E-Mail-Template aus dem templates/emails/ Verzeichnis
     * 
     * @param string $templateName Der Name des Templates ohne .html5 Endung
     * @return string Der Inhalt des Templates oder leerer String im Fehlerfall
     */
    public static function loadTemplate(string $templateName): string
    {
        if (empty($templateName)) {
            self::logError('Leerer Template-Name übergeben');
            return '';
        }
        
        try {
            // In Contao 5.x den Projektpfad über Symfony ermitteln
            $container = self::getContainer();
            $projectDir = $container->getParameter('kernel.project_dir');
            
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
                    
                    if ($content !== false) {
                        self::logInfo('Template gefunden und geladen: ' . $path);
                        return $content;
                    }
                    
                    self::logWarning('Template existiert, konnte aber nicht gelesen werden: ' . $path);
                }
            }
            
            // Falls kein Template gefunden wurde
            self::logError('Kein Template in den angegebenen Pfaden gefunden für: ' . $templateName);
            return '';
            
        } catch (\Exception $e) {
            self::logError('Fehler beim Laden des E-Mail-Templates: ' . $e->getMessage(), ['exception' => $e]);
            return '';
        }
    }
    
    /**
     * Ersetzt Platzhalter im Template mit tatsächlichen Werten
     * 
     * @param string $template Der Template-Inhalt
     * @param array $data Assoziatives Array mit Platzhaltern und deren Werten
     * @return string Das gerenderte Template
     */
    public static function renderTemplate(string $template, array $data): string
    {
        if (empty($template)) {
            return '';
        }
        
        // Platzhalter ersetzen ({{placeholder}} durch tatsächlichen Wert)
        foreach ($data as $placeholder => $value) {
            $template = str_replace('{{' . $placeholder . '}}', $value, $template);
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
    public static function getRenderedEmail(string $templateName, array $data): string
    {
        $template = self::loadTemplate($templateName);
        return self::renderTemplate($template, $data);
    }
    
    /**
     * Generiert eine minimale HTML-Struktur für E-Mails
     * 
     * @param string $content Der Hauptinhalt der E-Mail
     * @param array $options Optionale Einstellungen (title, styles)
     * @return string Vollständige HTML-E-Mail
     */
    public static function wrapInBasicEmailTemplate(string $content, array $options = []): string
    {
        $title = $options['title'] ?? 'E-Mail';
        $additionalStyles = $options['styles'] ?? '';
        
        return sprintf(
            '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>%s</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; background-color: #f5f5f5; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #2c3e50; }
        p { line-height: 1.6; margin: 16px 0; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        %s
    </style>
</head>
<body>
    <div class="email-container">
        %s
    </div>
</body>
</html>',
            htmlspecialchars($title),
            $additionalStyles,
            $content
        );
    }
    
    /**
     * Schreibt eine Fehler-Nachricht ins Log
     */
    private static function logError(string $message, array $context = []): void
    {
        if (class_exists('\Contao\System') && \Contao\System::getContainer()->has('monolog.logger.contao')) {
            \Contao\System::getContainer()->get('monolog.logger.contao')->error($message, $context);
        }
        error_log('[EmailHelper ERROR] ' . $message);
    }
    
    /**
     * Schreibt eine Info-Nachricht ins Log
     */
    private static function logInfo(string $message, array $context = []): void
    {
        if (class_exists('\Contao\System') && \Contao\System::getContainer()->has('monolog.logger.contao')) {
            \Contao\System::getContainer()->get('monolog.logger.contao')->info($message, $context);
        }
    }
    
    /**
     * Schreibt eine Warnung ins Log
     */
    private static function logWarning(string $message, array $context = []): void
    {
        if (class_exists('\Contao\System') && \Contao\System::getContainer()->has('monolog.logger.contao')) {
            \Contao\System::getContainer()->get('monolog.logger.contao')->warning($message, $context);
        }
        error_log('[EmailHelper WARNING] ' . $message);
    }
} 