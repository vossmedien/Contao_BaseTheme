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

class HeadlineHelper
{
    public static function generateHeadlineHTML(
        $topline = '',
        $headline = '',
        $subline = '',
        $headlineType = 'h2',
        $animationType = 'animate__fadeInUp',
        $textColor = '',
        $onlyStyle = false,
        $headlineClass = ''
    )
    {
        // Sicherstellen, dass alle Variablen Strings sind
        $topline = $topline ?? '';
        $headline = $headline ?? '';
        $subline = $subline ?? '';
        $headlineType = $headlineType ?? 'h2';
        $animationType = $animationType ?? 'animate__fadeIn';
        $textColor = $textColor ?? '';
        $headlineClass = $headlineClass ?? '';

        $textColorStyle = $textColor ? "color:{$textColor};" : '';
        $headlineClassAttribute = "class=\"ce--headline hl-{$headlineType}" . ($headlineClass ? " {$headlineClass}" : '') . "\"";

        $headlineHTML = "";

        if ($headline || $topline || $subline) {

            $headlineHTML = "<div {$headlineClassAttribute}>";

            if ($topline) {
                $headlineHTML .= "<span data-animation=\"{$animationType}\" class=\"ce--topline\">";
                $headlineHTML .= html_entity_decode($topline, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            if (!$onlyStyle && $headline != "") {
                $headlineHTML .= "<{$headlineType} data-animation=\"{$animationType}\" style=\"{$textColorStyle}\">";
                $headlineHTML .= html_entity_decode($headline, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $headlineHTML .= "</{$headlineType}>";
            } elseif ($headline != "") {
                $headlineHTML .= "<span data-animation=\"{$animationType}\" class=\"{$headlineType}\" style=\"{$textColorStyle}\">";
                $headlineHTML .= html_entity_decode($headline, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            if ($subline) {
                $headlineHTML .= "<span data-animation=\"{$animationType}\" class=\"ce--subline\">";
                $headlineHTML .= html_entity_decode($subline, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            $headlineHTML .= "</div>";
        }
        return $headlineHTML;
    }
}

