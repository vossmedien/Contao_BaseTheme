<?php

namespace VSM_HelperFunctions;

class HeadlineHelper
{
    public static function generateHeadlineHTML(
        $topline = '',
        $headline,
        $subline = '',
        $headlineType = 'h2',
        $animationType = 'animate__fadeInUp',
        $textColor = '',
        $onlyStyle = false,
        $headlineClass = ''
    )
    {
        $textColorStyle = $textColor ? "color:{$textColor};" : '';

        // Hier wird die optionale Klasse hinzugefügt, falls vorhanden.
        $headlineClassAttribute = "class=\"ce--headline hl-{$headlineType}" . ($headlineClass ? " {$headlineClass}" : '') . "\"";

        $headlineHTML = "";

        if ($headline || $topline || $subline) {

            $headlineHTML = "<div {$headlineClassAttribute} data-aos=\"{$animationType}\"><div class='container'>";

            if ($topline) {
                $headlineHTML .= "<span class=\"ce--topline\">";
                $headlineHTML .= htmlspecialchars($topline, ENT_QUOTES, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            if (!$onlyStyle && $headline != "") {
                $headlineHTML .= "<{$headlineType} style=\"{$textColorStyle}\">";
                $headlineHTML .= htmlspecialchars($headline, ENT_QUOTES, 'UTF-8');
                $headlineHTML .= "</{$headlineType}>";
            } elseif ($headline != "") {
                $headlineHTML .= "<span class=\"{$headlineType}\" style=\"{$textColorStyle}\">";
                $headlineHTML .= htmlspecialchars($headline, ENT_QUOTES, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            if ($subline) {
                $headlineHTML .= "<span class=\"ce--subline\">";
                $headlineHTML .= htmlspecialchars($subline, ENT_QUOTES, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            $headlineHTML .= "</div></div>";
        }
        return $headlineHTML;
    }
}
