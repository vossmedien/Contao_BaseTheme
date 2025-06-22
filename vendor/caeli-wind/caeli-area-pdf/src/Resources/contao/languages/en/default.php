<?php

declare(strict_types=1);

/*
 * Caeli Area PDF Bundle - English translations
 */

// PDF Content - Main Page
$GLOBALS['TL_LANG']['caeli_area_pdf']['pdf'] = [
    'document_title' => 'Non-binding Initial Assessment of Your Property',
    'document_subject' => 'Land Leasing for Wind Energy',
    'main_title_1' => 'Non-binding Initial Assessment',
    'main_title_2' => 'of Your Property',
    'subtitle' => 'Your first step to successful land leasing for wind energy',
    'map_placeholder' => 'Map Integration',
    'property_data_title' => 'Your Property Data',
    'municipality' => 'Municipality',
    'district' => 'District',
    'area_size' => 'Area Size',
    'hectares' => 'ha',
    'geo_id' => 'Geo ID of the Wind Farm',
    'created_for' => 'Created by Caeli Wind for',
    'date_format' => 'm/d/Y - H:i'
];

// PDF Content - Results Page
$GLOBALS['TL_LANG']['caeli_area_pdf']['results'] = [
    'result_title' => 'Result of the Initial Assessment:',
    'result_subtitle' => 'Good Conditions with Economic Potential!',
    'congratulations' => 'Congratulations,',
    'recommendation' => 'Your location is likely suitable for economic use as a wind energy site. We recommend a detailed analysis by our expert team.',
    'wind_conditions_title' => 'Wind Conditions',
    'wind_conditions_text' => 'At the specified location, good wind conditions are present with a wind power density of %s W/m² to %s W/m².',
    'restrictions_title' => 'Restrictions',
    'restrictions_text' => 'Potentially up to %s%% of the property area can be used for wind farm development, considering spatial planning restrictions.',
    'grid_connection_title' => 'Grid Connection',
    'grid_connection_text' => 'For grid connection to the high-voltage network, between %s m and %s m of line construction is required.',
    'disclaimer' => 'Note: This assessment is based on rough estimates and does not replace a detailed analysis using precise mapping. This will be done in a personal consultation and through the later provision of your exact property data.',
    'copyright' => '© Caeli Wind GmbH',
    'imprint' => 'Imprint'
];

// PDF Content - Steps Page
$GLOBALS['TL_LANG']['caeli_area_pdf']['steps'] = [
    'title' => 'What happens next...',
    'subtitle_1' => 'From non-binding initial assessment to',
    'subtitle_2' => 'contract completion – 100% free for you!',
    'step_1_title' => 'Step 1: Personal Consultation & Expert Assessment',
    'step_1_text' => 'At the specified location, good wind conditions are present with a wind power density of 400 W/m² to 510 W/m². In a virtual consultation appointment, we present our services to you and conduct a more precise evaluation of your property using map marking.',
    'step_2_title' => 'Step 2: Joint Planning & Goal Setting',
    'step_2_text' => 'You receive the analysis results for your area, necessary information, and binding answers to your questions. Together we clarify the next steps to market your potential wind energy site simply and securely – entirely according to your personal preferences.',
    'step_3_title' => 'Step 3: Access to the Marketplace & Successful Mediation',
    'step_3_text' => 'As part of the professional marketing of your property, you receive exclusive access to our online marketplace for wind energy sites. Thanks to the large number of renowned project developers, we find the right partner for your wind farm project together. We accompany you step by step throughout the entire process – from initial consultation to final negotiation.',
    'property_data_title' => 'Your Property Data'
];

// API related
$GLOBALS['TL_LANG']['caeli_area_pdf']['api'] = [
    'map_load_error' => 'Map image could not be loaded!'
];

// Backend messages
$GLOBALS['TL_LANG']['caeli_area_pdf']['backend'] = [
    'info_message' => 'This module generates PDF reports for wind energy area assessments. It is only active in the frontend when a parkid parameter is present.'
]; 