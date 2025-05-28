<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Hubspot Connect.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-hubspot-connect
 */

// Paletten erweitern
$caeliHubspotFieldTypes = ['text', 'textarea', 'select', 'checkbox', 'radio', 'password', 'upload', 'hidden', 'captcha'];
$caeliHubspotLegendString = '{hubspot_legend},hubspotFieldName';

foreach ($caeliHubspotFieldTypes as $fieldType) {
    // Ensure the palette exists and is a string for the current field type
    if (!isset($GLOBALS['TL_DCA']['tl_form_field']['palettes'][$fieldType]) || !is_string($GLOBALS['TL_DCA']['tl_form_field']['palettes'][$fieldType])) {
        // Palette for this field type is not set or not a string, skip.
        // One could log this: error_log("HubSpot Connect: Palette for '{$fieldType}' not found/not string.");
        continue;
    }

    // Use a reference to modify the palette directly
    $palette = &$GLOBALS['TL_DCA']['tl_form_field']['palettes'][$fieldType];

    // Skip if the HubSpot field name is already in the palette
    if (str_contains($palette, 'hubspotFieldName')) {
        continue;
    }

    $inserted = false;
    // Define common expert legend variants to search for. Check more specific first.
    $expertLegendPatterns = [
        '{expert_legend:hidden}',
        '{expert_legend:hide}',
        '{expert_legend}'
    ];

    foreach ($expertLegendPatterns as $pattern) {
        if (str_contains($palette, $pattern)) {
            $palette = str_replace($pattern, $caeliHubspotLegendString . ';' . $pattern, $palette);
            $inserted = true;
            break; // Exit loop once replaced
        }
    }

    if (!$inserted) {
        // If no expert_legend variant was found, append the HubSpot field string.
        // Add a semicolon separator if the palette is not empty and does not already end with one.
        if (!empty($palette) && !str_ends_with($palette, ';')) {
            $palette .= ';';
        }
        $palette .= $caeliHubspotLegendString;
    }
}

// Hubspot Feld-Mapping
$GLOBALS['TL_DCA']['tl_form_field']['fields']['hubspotFieldName'] = [
    'exclude'   => true,
    'inputType' => 'text', 
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''"
]; 