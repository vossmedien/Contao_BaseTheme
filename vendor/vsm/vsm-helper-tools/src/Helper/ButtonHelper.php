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
        // Button-Eigenschaften extrahieren und bereinigen
        $animationType = self::cleanInput($btn->animation_type ?? 'animate__fadeInUp');
        $buttonType = self::cleanInput($btn->link_type ?? '');
        $buttonSize = self::cleanInput($btn->link_size ?? '');
        $buttonUrl = self::cleanInput($btn->link_url ?? '');
        $linkTarget = self::cleanInput($btn->new_tab ?? '');
        $buttonId = self::cleanInput($btn->link_id ?? '');
        $enableTracking = (bool)($btn->enable_tracking ?? false);
        $trackingPosition = self::cleanInput($btn->tracking_position ?? '');
        $linkBetreff = self::cleanInput($btn->link_betreff ?? '');

        $linkText = $btn->link_text ? 
            html_entity_decode($btn->link_text, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';

        // CSS-Klassen zusammenstellen
        $buttonClasses = self::buildButtonClasses($buttonSize, $buttonType);
        
        // Zusätzliche Attribute erstellen
        $additionalAttributes = self::buildAdditionalAttributes($linkTarget);

        // Betreff für E-Mail-Links
        $betreff = $linkBetreff ? "?subject=" . urlencode($linkBetreff) : '';

        // Button HTML zusammenstellen
        $buttonHTML = "<a class=\"{$buttonClasses}\"";
        
        if ($includeAnimation) {
            $buttonHTML .= " data-animation=\"{$animationType}\"";
        }
        
        $buttonHTML .= " href=\"{$buttonUrl}{$betreff}\"";

        if (!empty($buttonId)) {
            $buttonHTML .= " id=\"{$buttonId}\"";
        }

        // Tracking hinzufügen wenn aktiviert
        if ($enableTracking) {
            $trackingCode = self::buildTrackingCode($trackingPosition, $linkText);
            if ($trackingCode) {
                $buttonHTML .= " onclick=\"" . $trackingCode . "\"";
            }
        }

        if (!empty($additionalAttributes)) {
            $buttonHTML .= " " . implode(" ", $additionalAttributes);
        }

        $buttonHTML .= ">{$linkText}</a>";

        return $buttonHTML;
    }

    /**
     * Erstellt CSS-Klassen für den Button
     */
    private static function buildButtonClasses(string $buttonSize, string $buttonType): string
    {
        return trim("btn {$buttonSize} {$buttonType}");
    }

    /**
     * Erstellt zusätzliche HTML-Attribute basierend auf dem Link-Target
     */
    private static function buildAdditionalAttributes(string $linkTarget): array
    {
        $attributes = [];

        switch ($linkTarget) {
            case 'new_tab':
            case '1':
                $attributes[] = 'target="_blank"';
                $attributes[] = 'rel="noopener noreferrer"';
                break;
            case 'lightbox':
                // Lightbox-Klasse wird bereits in buildButtonClasses hinzugefügt
                break;
        }

        return $attributes;
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
     * Schreibt eine Fehler-Nachricht ins Log
     */
    private static function logError(string $message): void
    {
        error_log('[ButtonHelper ERROR] ' . $message);
    }
}