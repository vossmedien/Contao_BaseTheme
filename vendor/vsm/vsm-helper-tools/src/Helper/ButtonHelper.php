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
namespace Vsm\VsmHelperTools\Helper;

/**
 * Button Helper
 * 
 * Generiert HTML-Code für Buttons mit verschiedenen Optionen wie
 * Styling, Tracking, Links und Animationen.
 */
class ButtonHelper
{
    /**
     * Holt die Button-Konfiguration aus dem GlobalElementConfig
     * 
     * @param bool $includeAnimation Ob Animation-Optionen enthalten sein sollen
     * @return array Die Button-Konfiguration
     */
    public static function getButtonConfig(bool $includeAnimation = true): array
    {
        return GlobalElementConfig::getButtonConfig($includeAnimation);
    }

    /**
     * Generiert HTML-Code für eine Button-Liste
     * 
     * @param array $buttons Array von Button-Objekten
     * @param string|null $css Zusätzliche CSS-Klassen für den Container
     * @param bool $includeAnimation Ob Animationen enthalten sein sollen
     * @return string Der generierte HTML-Code
     */
    public static function generateButtonHTML(
        ?array $buttons = null, 
        ?string $css = null, 
        ?bool $includeAnimation = true
    ): string {
        try {
            if (empty($buttons) || !is_array($buttons)) {
                return '';
            }

            $includeAnimation = (bool)($includeAnimation ?? true);
            $css = self::cleanInput($css);
            $buttonHTML = "<div class=\"ce--buttons" . (!empty($css) ? " {$css}" : "") . "\">";

            foreach ($buttons as $btn) {
                $buttonHTML .= self::generateSingleButton($btn, $includeAnimation);
            }

            $buttonHTML .= "</div>";

            return $buttonHTML;
        } catch (\Exception $e) {
            self::logError('Fehler bei Button-Generierung: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Generiert HTML-Code für einen einzelnen Button
     * 
     * @param object $btn Das Button-Objekt
     * @param bool $includeAnimation Ob Animation enthalten sein soll
     * @return string Der generierte HTML-Code für den Button
     */
    private static function generateSingleButton($btn, bool $includeAnimation): string
    {
        // Grundlegende Button-Eigenschaften
        $buttonUrl = self::cleanInput($btn->link_url ?? '');
        $linkText = $btn->link_text ? 
            html_entity_decode($btn->link_text, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
        
        // Intelligente Automatisierung
        $autoAttributes = self::getIntelligentAttributes($buttonUrl, $linkText);
        
        // Manuelle Überschreibungen (falls gesetzt)
        $manualAttributes = self::getManualAttributes($btn);
        
        // Zusammenführen (manuelle Attribute überschreiben automatische)
        $finalAttributes = array_merge($autoAttributes, $manualAttributes);
        
        // Button-Styling
        $buttonType = self::cleanInput($btn->link_type ?? '');
        $buttonSize = self::cleanInput($btn->link_size ?? '');
        $buttonClasses = self::buildButtonClasses($buttonSize, $buttonType);
        
        // HTML generieren
        return self::buildButtonHtml($finalAttributes, $buttonClasses, $includeAnimation);
    }

    /**
     * Erstellt CSS-Klassen für den Button
     */
    private static function buildButtonClasses(string $buttonSize, string $buttonType): string
    {
        return trim("btn {$buttonSize} {$buttonType}");
    }



    /**
     * Erstellt den Tracking-Code für Analytics
     */
    private static function buildTrackingCode(string $trackingPosition, string $linkText): string
    {
        if (empty($trackingPosition) || empty($linkText)) {
            return '';
        }

        $trackingText = strip_tags($linkText);
        $trackingText = trim(preg_replace('/\s+/', ' ', $trackingText));
        
        return "window.pushToDataLayer('Button', '" . 
               addslashes($trackingPosition) . "', '" . 
               addslashes($trackingText) . "');";
    }

    /**
     * Bereinigt Eingabe-Strings
     */
    private static function cleanInput(?string $input): string
    {
        return $input !== null ? trim((string)$input) : '';
    }

    /**
     * Prüft, ob es sich um einen externen Link handelt
     */
    private static function isExternalLink(string $url): bool
    {
        // Relative Links oder Anker sind nicht extern
        if (empty($url) || $url[0] === '/' || $url[0] === '#') {
            return false;
        }

        // E-Mail-Links und andere Protokolle sind nicht extern im klassischen Sinne
        if (strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) {
            return false;
        }

        $parsedUrl = parse_url($url);
        
        // Wenn keine Host-Information vorhanden ist, ist es nicht extern
        if (empty($parsedUrl['host'])) {
            return false;
        }

        // Aktuelle Domain ermitteln
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        
        // Prüfen, ob es sich um eine andere Domain handelt
        return $parsedUrl['host'] !== $currentHost;
    }

    /**
     * Automatische Erkennung von Attributen basierend auf URL und Kontext
     */
    private static function getIntelligentAttributes(string $url, string $linkText): array
    {
        $attributes = [
            'url' => $url,
            'text' => $linkText,
            'title' => self::generateAutoTitle($linkText),
            'aria_label' => '', // Wird später aus title generiert falls leer
            'target' => '',
            'rel' => [],
            'download' => false,
            'hreflang' => '',
            'subject' => '',
            'animation' => 'animate__fadeInUp',
            'tracking' => '',
            'id' => '',
            'data_attributes' => []
        ];

        // Externe Links automatisch erkennen
        if (self::isExternalLink($url)) {
            $attributes['target'] = '_blank';
            $attributes['rel'][] = 'noopener';
            $attributes['rel'][] = 'noreferrer';
            
            // Hreflang aus Domain ableiten
            $attributes['hreflang'] = self::extractLanguageFromUrl($url);
        }

        // Download-Links automatisch erkennen
        if (self::isDownloadLink($url)) {
            $attributes['download'] = true;
        }

        // E-Mail-Links
        if (strpos($url, 'mailto:') === 0) {
            $attributes['title'] = 'E-Mail senden: ' . $attributes['title'];
        }

        // Telefon-Links
        if (strpos($url, 'tel:') === 0) {
            $attributes['title'] = 'Anrufen: ' . str_replace('tel:', '', $url);
        }

        return $attributes;
    }

    /**
     * Manuelle Attribute aus dem Button-Objekt extrahieren
     */
    private static function getManualAttributes($btn): array
    {
        $manual = [];

        // Nur setzen wenn explizit angegeben
        if (!empty($btn->link_title)) {
            $manual['title'] = self::cleanInput($btn->link_title);
        }

        if (!empty($btn->link_id)) {
            $manual['id'] = self::cleanInput($btn->link_id);
        }

        if (!empty($btn->new_tab)) {
            $manual['target'] = '_blank';
            $manual['rel'] = ['noopener', 'noreferrer'];
        }

        if (!empty($btn->link_betreff)) {
            $manual['subject'] = self::cleanInput($btn->link_betreff);
        }

        if (!empty($btn->tracking_position)) {
            $manual['tracking'] = self::cleanInput($btn->tracking_position);
        }

        if (!empty($btn->animation_type)) {
            $manual['animation'] = self::cleanInput($btn->animation_type);
        }

        return $manual;
    }

    /**
     * Baut das finale Button-HTML zusammen
     */
    private static function buildButtonHtml(array $attributes, string $buttonClasses, bool $includeAnimation): string
    {
        $url = $attributes['url'];
        $text = $attributes['text'];
        $title = $attributes['title'];
        $ariaLabel = !empty($attributes['aria_label']) ? $attributes['aria_label'] : $title;

        // Betreff für E-Mail-Links
        $subject = !empty($attributes['subject']) ? "?subject=" . urlencode($attributes['subject']) : '';

        // Button HTML zusammenbauen
        $html = "<a class=\"{$buttonClasses}\"";

        if ($includeAnimation && !empty($attributes['animation'])) {
            $html .= " data-animation=\"{$attributes['animation']}\"";
        }

        $html .= " href=\"{$url}{$subject}\"";

        if (!empty($attributes['id'])) {
            $html .= " id=\"{$attributes['id']}\"";
        }

        if (!empty($title)) {
            $html .= " title=\"" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "\"";
        }

        if (!empty($ariaLabel)) {
            $html .= " aria-label=\"" . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') . "\"";
        }

        if (!empty($attributes['target'])) {
            $html .= " target=\"{$attributes['target']}\"";
        }

        if (!empty($attributes['rel'])) {
            $relString = implode(' ', array_unique($attributes['rel']));
            $html .= " rel=\"{$relString}\"";
        }

        if ($attributes['download']) {
            $html .= " download";
        }

        if (!empty($attributes['hreflang'])) {
            $html .= " hreflang=\"{$attributes['hreflang']}\"";
        }

        // Tracking
        if (!empty($attributes['tracking'])) {
            $trackingCode = self::buildTrackingCode($attributes['tracking'], $text);
            if ($trackingCode) {
                $html .= " onclick=\"{$trackingCode}\"";
            }
        }

        $html .= ">{$text}</a>";

        return $html;
    }

    /**
     * Schreibt eine Fehler-Nachricht ins Log
     */
    private static function logError(string $message): void
    {
        error_log('[ButtonHelper ERROR] ' . $message);
    }



    /**
     * Generiert automatisch einen Title aus dem Link-Text
     */
    private static function generateAutoTitle(string $linkText): string
    {
        // HTML-Tags entfernen und bereinigen
        $title = strip_tags($linkText);
        $title = trim(preg_replace('/\s+/', ' ', $title));
        
        return $title;
    }

    /**
     * Versucht die Sprache aus einer URL zu extrahieren
     */
    private static function extractLanguageFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        
        if (empty($parsedUrl['host'])) {
            return '';
        }

        $host = $parsedUrl['host'];
        
        // Bekannte TLD-basierte Sprachen
        $tldLanguages = [
            '.de' => 'de',
            '.fr' => 'fr',
            '.it' => 'it',
            '.es' => 'es',
            '.nl' => 'nl',
            '.co.uk' => 'en-GB',
            '.com.au' => 'en-AU',
        ];

        foreach ($tldLanguages as $tld => $lang) {
            if (substr($host, -strlen($tld)) === $tld) {
                return $lang;
            }
        }

        // Subdomain-basierte Sprachen (z.B. en.example.com)
        if (preg_match('/^([a-z]{2})\./', $host, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Prüft ob es sich um einen Download-Link handelt
     */
    private static function isDownloadLink(string $url): bool
    {
        $downloadExtensions = [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip', 'rar', '7z', 'tar', 'gz',
            'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',
            'mp3', 'wav', 'mp4', 'avi', 'mkv', 'mov',
            'txt', 'csv', 'json', 'xml'
        ];

        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        if (empty($path)) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        return in_array($extension, $downloadExtensions);
    }
}