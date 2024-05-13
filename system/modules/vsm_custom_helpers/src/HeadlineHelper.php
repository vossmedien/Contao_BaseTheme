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
        $headlineClassAttribute = "class=\"ce--headline hl-{$headlineType}" . ($headlineClass ? " {$headlineClass}" : '') . "\"";

        $headlineHTML = "";

        if ($headline || $topline || $subline) {

            $headlineHTML = "<div {$headlineClassAttribute} >";

            if ($topline) {
                $headlineHTML .= "<span data-aos=\"{$animationType}\" class=\"ce--topline\">";
                $headlineHTML .= htmlspecialchars($topline, ENT_QUOTES, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            if (!$onlyStyle && $headline != "") {
                $headlineHTML .= "<{$headlineType} data-aos=\"{$animationType}\" style=\"{$textColorStyle}\">";
                $headlineHTML .= htmlspecialchars($headline, ENT_QUOTES, 'UTF-8');
                $headlineHTML .= "</{$headlineType}>";
            } elseif ($headline != "") {
                $headlineHTML .= "<span data-aos=\"{$animationType}\" class=\"{$headlineType}\" style=\"{$textColorStyle}\">";
                $headlineHTML .= htmlspecialchars($headline, ENT_QUOTES, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            if ($subline) {
                $headlineHTML .= "<span data-aos=\"{$animationType}\" class=\"ce--subline\">";
                $headlineHTML .= htmlspecialchars($subline, ENT_QUOTES, 'UTF-8');
                $headlineHTML .= "</span>";
            }

            $headlineHTML .= "</div>";
        }
        return $headlineHTML;
    }
}
