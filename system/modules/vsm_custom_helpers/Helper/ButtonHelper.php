<?php

namespace VSM_HelperFunctions;

class ButtonHelper
{
    public static function getButtonConfig()
    {
        return GlobalElementConfig::getButtonConfig();
    }


    public static function generateButtonHTML($buttons, $css = null)
    {
        $buttonHTML = "";

        if (!empty($buttons)) {
            $buttonHTML .= "<div class=\"ce--buttons" . (!empty($css) ? " {$css}" : "") . "\">";

            foreach ($buttons as $btn) {
                $animationType = $btn->animation_type ?? 'animate__fadeInUp';
                $buttonType = $btn->link_type ?? '';
                $buttonSize = $btn->link_size ?? '';
                $buttonUrl = $btn->link_url ?? '';
                $linkTarget = $btn->new_tab ?? '';
                $buttonId = $btn->link_id ?? ''; // Neue Zeile für die Button-ID

                // Behandle HTML im Linktext korrekt
                $linkText = $btn->link_text ? html_entity_decode($btn->link_text, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';

                $buttonClasses = "btn {$buttonSize} {$buttonType}";
                $additionalAttributes = [];

                switch ($linkTarget) {
                    case 'new_tab':
                        $additionalAttributes[] = 'target="_blank"';
                        break;
                    case 'lightbox':
                        $buttonClasses .= ' lightbox';
                        break;
                    default:
                        // Standardfall: Öffnen im selben Tab
                        break;
                }

                // Füge Betreff hinzu, falls vorhanden
                $betreff = $btn->link_betreff ? "?subject=" . urlencode($btn->link_betreff) : '';

                $buttonHTML .= "<a class=\"{$buttonClasses}\"";
                $buttonHTML .= " data-aos=\"{$animationType}\"";
                $buttonHTML .= " href=\"{$buttonUrl}{$betreff}\"";

                // Füge ID hinzu, falls vorhanden
                if (!empty($buttonId)) {
                    $buttonHTML .= " id=\"{$buttonId}\"";
                }

                // Füge zusätzliche Attribute hinzu
                if (!empty($additionalAttributes)) {
                    $buttonHTML .= " " . implode(" ", $additionalAttributes);
                }

                $buttonHTML .= ">{$linkText}</a>";
            }

            $buttonHTML .= "</div>";
        }

        return $buttonHTML;
    }
}