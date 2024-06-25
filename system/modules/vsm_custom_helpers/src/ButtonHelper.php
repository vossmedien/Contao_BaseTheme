<?php

namespace VSM_HelperFunctions;

class ButtonHelper
{
    public static function generateButtonHTML($buttons, $css = null)
    {
        $buttonHTML = "";

        if (!empty($buttons)) {
            $buttonHTML .= "<div class=\"ce--buttons" . (!empty($css) ? " {$css}" : "") . "\">";

            foreach ($buttons as $btn) {
                $animationType = $btn->animation_type ?? 'animate__fadeInUp';
                $newTab = $btn->new_tab ? ' target="_blank"' : '';
                $betreff = $btn->link_betreff ? "?subject=" . urlencode($btn->link_betreff) : '';
                $buttonType = $btn->link_type ?? '';
                $buttonSize = $btn->link_size ?? '';
                $buttonUrl = $btn->link_url ?? '';
                $linkText = $btn->link_text ? htmlspecialchars_decode(htmlspecialchars($btn->link_text, ENT_QUOTES, 'UTF-8')) : '';
                $buttonHTML .= "<a class=\" btn {$buttonSize} {$buttonType}\"";
                $buttonHTML .= " data-aos=\"{$animationType}\"";
                $buttonHTML .= " href=\"{$buttonUrl}{$betreff}\"{$newTab}>{$linkText}</a>";
            }

            $buttonHTML .= "</div>";
        }

        return $buttonHTML;
    }
}
