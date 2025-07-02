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
 * Headline Helper
 * 
 * Generiert HTML-Code für strukturierte Überschriften mit optionalen
 * Bestandteilen wie Topline, Hauptüberschrift und Subline.
 */
class HeadlineHelper
{
    /**
     * Generiert HTML-Code für eine vollständige Überschrift
     * 
     * @param string $topline Optionale Topline über der Hauptüberschrift
     * @param string $headline Die Hauptüberschrift
     * @param string $subline Optionale Subline unter der Hauptüberschrift
     * @param string $headlineType HTML-Tag für die Überschrift (h1-h6)
     * @param string|null $animationType CSS-Animation (derzeit nicht implementiert)
     * @param string $textColor CSS-Farbwert für die Überschrift
     * @param bool $onlyStyle Wenn true, wird span statt h-Tag verwendet
     * @param string $headlineClass Zusätzliche CSS-Klassen
     * @return string Der generierte HTML-Code
     */
    public static function generateHeadlineHTML(
        ?string $topline = '',
        ?string $headline = '',
        ?string $subline = '',
        ?string $headlineType = 'h2',
        ?string $animationType = null,
        ?string $textColor = '',
        ?bool $onlyStyle = false,
        ?string $headlineClass = ''
    ): string {
        try {
            // Eingaben bereinigen und validieren
            $topline = self::cleanInput($topline);
            $headline = self::cleanInput($headline);
            $subline = self::cleanInput($subline);
            $headlineType = self::validateHeadlineType($headlineType ?? 'h2');
            $textColor = self::cleanInput($textColor);
            $headlineClass = self::cleanInput($headlineClass);
            $onlyStyle = (bool)($onlyStyle ?? false);

            // Wenn keine Inhalte vorhanden sind, leeren String zurückgeben
            if (empty($headline) && empty($topline) && empty($subline)) {
                return '';
            }

            $textColorStyle = $textColor ? "color:{$textColor};" : '';
            $headlineClassAttribute = self::buildClassAttribute($headlineType, $headlineClass);

            $headlineHTML = "<div {$headlineClassAttribute}>";

            // Topline hinzufügen
            if ($topline) {
                $headlineHTML .= "<span class=\"ce--topline\">";
                $headlineHTML .= html_entity_decode($topline, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            // Hauptüberschrift hinzufügen
            if ($headline !== '') {
                if (!$onlyStyle) {
                    $headlineHTML .= "<{$headlineType} style=\"{$textColorStyle}\">";
                    $headlineHTML .= html_entity_decode($headline, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $headlineHTML .= "</{$headlineType}>";
                } else {
                    $headlineHTML .= "<span class=\"{$headlineType}\" style=\"{$textColorStyle}\">";
                    $headlineHTML .= html_entity_decode($headline, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $headlineHTML .= "</span>";
                }
            }

            // Subline hinzufügen
            if ($subline) {
                $headlineHTML .= "<span class=\"ce--subline\">";
                $headlineHTML .= html_entity_decode($subline, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            $headlineHTML .= "</div>";

            return $headlineHTML;
        } catch (\Exception $e) {
            self::logError('Fehler bei Headline-Generierung: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Bereinigt Eingabe-Strings
     */
    private static function cleanInput(?string $input): string
    {
        return $input !== null ? trim((string)$input) : '';
    }

    /**
     * Validiert den Headline-Type
     */
    private static function validateHeadlineType(string $headlineType): string
    {
        $validTypes = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        return in_array($headlineType, $validTypes) ? $headlineType : 'h2';
    }

    /**
     * Erstellt das class-Attribut für die Headline
     */
    private static function buildClassAttribute(string $headlineType, string $headlineClass): string
    {
        $classes = "ce--headline hl-{$headlineType}";
        if ($headlineClass) {
            $classes .= " {$headlineClass}";
        }
        return "class=\"{$classes}\"";
    }

    /**
     * Schreibt eine Fehler-Nachricht ins Log
     */
    private static function logError(string $message): void
    {
        error_log('[HeadlineHelper ERROR] ' . $message);
    }
}
