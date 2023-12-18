<?php

namespace VSM_HelperFunctions;

class ButtonHelper
{
    public static function generateButtonHTML($buttons)
    {
        $buttonHTML = "";

        if (!empty($buttons)) {
            $buttonHTML .= "<div class=\"ce--buttons mt-3\">";

            foreach ($buttons as $btn) {
                $animationType = !empty($btn->animation_type) ? $btn->animation_type : 'animate__fadeInUp';
                $newTab = !empty($btn->new_tab) ? ' target="_blank"' : '';
                $betreff = !empty($btn->link_betreff) ? "?subject=" . urlencode($btn->link_betreff) : '';
                $buttonType = !empty($btn->link_type) ? $btn->link_type : '';
                $buttonSize = !empty($btn->link_size) ? $btn->link_size : '';
                $buttonUrl = !empty($btn->link_url) ? $btn->link_url : '';
                $linkText = !empty($btn->link_text) ? htmlspecialchars_decode(htmlspecialchars($btn->link_text, ENT_QUOTES, 'UTF-8')) : '';

                $buttonHTML .= "<a class=\"d-inline-block btn {$buttonSize} {$buttonType}\"";
                $buttonHTML .= " data-aos=\"{$animationType}\"";
                $buttonHTML .= " href=\"{$buttonUrl}{$betreff}\"{$newTab}>{$linkText}</a>";
            }

            $buttonHTML .= "</div>";
        }

        return $buttonHTML;
    }
}
