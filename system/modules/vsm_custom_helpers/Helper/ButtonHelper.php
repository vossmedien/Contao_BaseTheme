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
                $buttonId = $btn->link_id ?? '';
                $enableTracking = $btn->enable_tracking ?? false;
                $trackingPosition = $btn->tracking_position ?? '';

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
                        break;
                }

                $betreff = $btn->link_betreff ? "?subject=" . urlencode($btn->link_betreff) : '';

                $buttonHTML .= "<a class=\"{$buttonClasses}\"";
                $buttonHTML .= " data-animation=\"{$animationType}\"";
                $buttonHTML .= " href=\"{$buttonUrl}{$betreff}\"";

                if (!empty($buttonId)) {
                    $buttonHTML .= " id=\"{$buttonId}\"";
                }

                // Add tracking if enabled
                if ($enableTracking) {
                    $trackingText = strip_tags($linkText); // Remove HTML tags
                    $trackingText = trim(preg_replace('/\s+/', ' ', $trackingText)); // Remove extra whitespace
                    $trackingCode = "window.pushToDataLayer('Button', '" . addslashes($trackingPosition) . "', '" . addslashes($trackingText) . "');";
                    $buttonHTML .= " onclick=\"" . $trackingCode . "\"";
                }

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