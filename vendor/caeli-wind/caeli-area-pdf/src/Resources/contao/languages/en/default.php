<?php

declare(strict_types=1);

/*
 * Caeli Area PDF Bundle - English translations
 */

// PDF Content - Main Page
$GLOBALS['TL_LANG']['caeli_area_pdf']['pdf'] = [
    'document_title' => 'Preliminary Site Assessment',
    'document_subject' => 'Land Leasing for Wind Energy',
    'main_title_1' => 'Preliminary Site Assessment',
    'main_title_2' => '',
    'subtitle' => 'Your first step toward leasing your land for wind energy',
    'map_placeholder' => 'Map Integration',
    'property_data_title' => 'Your Site Information',
    'municipality' => 'Municipality',
    'district' => 'District',
    'area_size' => 'Area size',
    'hectares' => 'ha',
    'geo_id' => 'Site ID',
    'created_for' => 'Created by Caeli Wind for',
    'date_format' => 'm/d/Y - H:i'
];

// PDF Content - Results Page
$GLOBALS['TL_LANG']['caeli_area_pdf']['results'] = [
    'result_title' => 'Assessment Result:',
    'result_subtitle' => 'Strong potential for wind energy development',
    'congratulations' => 'Congratulations!',
    'recommendation' => 'Our preliminary review suggests your site is well suited for a commercially viable wind energy project. We recommend a more detailed assessment by our expert team.',
    'wind_conditions_title' => 'Wind conditions',
    'wind_conditions_text' => 'The specified site shows promising wind conditions, with an estimated wind power density of %s W/m² to %s W/m².',
    'restrictions_title' => 'Development Potential',
    'restrictions_text' => 'Taking current spatial planning constraints into account, approximately up to %s%% of the total area is likely usable for wind farm development.',
    'grid_connection_title' => 'Grid connection',
    'grid_connection_text' => 'To connect to the high-voltage grid, an estimated line construction of %s to %s metres would be required.',
    'disclaimer' => 'Please note: This is a non-binding assessment based on preliminary data and does not replace a precise on-site analysis or map-based evaluation. A detailed review will be carried out during a personal consultation and based on your site\'s specific data.',
    'copyright' => '© Caeli Wind GmbH',
    'imprint' => 'Imprint'
];

// PDF Content - Steps Page
$GLOBALS['TL_LANG']['caeli_area_pdf']['steps'] = [
    'title' => 'What happens next?',
    'subtitle_1' => 'From this initial, no-obligation assessment to a signed lease agreement – ',
    'subtitle_2' => 'at no cost to you.',
    'step_1_title' => 'Step 1: Personal Consultation & Technical Review',
    'step_1_text' => 'Your site shows good wind conditions with a power density between 400 W/m² and 510 W/m². We\'ll arrange a virtual meeting to walk you through the results and present a detailed, map-based assessment of your land.',
    'step_2_title' => 'Step 2: Joint Planning & Goal Setting',
    'step_2_text' => 'You\'ll receive a detailed analysis of your land, all relevant information, and clear answers to your questions. Together, we\'ll define the next steps to market your site in a simple, transparent, and secure way – aligned with your preferences.',
    'step_3_title' => 'Step 3: Access to the Marketplace & Project Matchmaking',
    'step_3_text' => 'As part of the professional marketing of your property, you will receive exclusive access to our online marketplace for wind energy sites. Thanks to a large number of renowned project developers, we\'ll help you find the right partner for your project – and support you every step of the way, from the first contact to final contract negotiations.',
    'property_data_title' => 'Your Site Information'
];

// API related
$GLOBALS['TL_LANG']['caeli_area_pdf']['api'] = [
    'map_load_error' => 'Map image could not be loaded!'
];

// Backend messages
$GLOBALS['TL_LANG']['caeli_area_pdf']['backend'] = [
    'info_message' => 'This module generates PDF reports for wind energy area assessments. It is only active in the frontend when a parkid parameter is present.'
]; 