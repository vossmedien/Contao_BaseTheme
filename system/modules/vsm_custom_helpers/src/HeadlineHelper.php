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
                $headlineHTML .= $topline;
                $headlineHTML .= "</span>";
            }

            if (!$onlyStyle && $headline != "") {
                $headlineHTML .= "<{$headlineType} data-aos=\"{$animationType}\" style=\"{$textColorStyle}\">";
                $headlineHTML .= $headline;
                $headlineHTML .= "</{$headlineType}>";
            } elseif ($headline != "") {
                $headlineHTML .= "<span data-aos=\"{$animationType}\" class=\"{$headlineType}\" style=\"{$textColorStyle}\">";
                $headlineHTML .= $headline;
                $headlineHTML .= "</span>";
            }

            if ($subline) {
                $headlineHTML .= "<span data-aos=\"{$animationType}\" class=\"ce--subline\">";
                $headlineHTML .= $subline;
                $headlineHTML .= "</span>";
            }

            $headlineHTML .= "</div>";
        }
        return $headlineHTML;
    }
}
