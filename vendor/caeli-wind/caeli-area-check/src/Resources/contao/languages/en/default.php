<?php

declare(strict_types=1);

/*
 * Caeli Area Check Bundle - English translations
 */

// Loading texts
$GLOBALS['TL_LANG']['caeli_area_check']['loading']['texts'] = [
    'checking_area' => 'We are checking your area',
    'wind_conditions' => 'Do the wind conditions fit?',
    'restrictions_check' => 'Are there restrictions?',
    'grid_connection' => 'Is a grid connection available?',
    'analyzing_potential' => 'Analyzing wind potential',
    'checking_nature' => 'Checking nature reserves',
    'calculating_economics' => 'Calculating economics',
    'checking_distances' => 'Checking distance regulations',
    'analyzing_capacity' => 'Analyzing grid capacity',
    'evaluating_quality' => 'Evaluating site quality',
];

// Form elements
$GLOBALS['TL_LANG']['caeli_area_check']['form'] = [
    'plz_placeholder' => 'Enter postal code',
    'plz_alert' => 'Please enter a complete 5-digit postal code.',
    'button' => [
        'check_area' => 'Show result',
        'restart' => 'Restart',
    ],
    'warning' => 'The selected area is larger than 700 hectares. Please reduce the area size.',
];

// Tutorial system
$GLOBALS['TL_LANG']['caeli_area_check']['tutorial'] = [
    'welcome' => [
        'title' => 'Welcome to your area check.',
        'content' => 'Discover the wind potential of your property in just a few steps. We\'ll show you briefly how it works. Simply click "Next".',
        'button_skip' => 'Skip',
        'button_next' => 'Next',
    ],
    'plz_input' => [
        'title' => 'Step 1: Your location matters.',
        'title_alt' => 'Here you start your area check.',
        'content' => 'Start by entering your location or postal code. This way we find the right map section for your area.',
        'button_back' => 'Back',
        'button_next' => 'Next',
    ],
    'polygon_edit' => [
        'title' => 'Step 2: Draw your area.',
        'title_alt' => 'Here you draw your area.',
        'content' => 'Now comes the exciting part: Move the map to the desired area and use the corner points of the polygon. Drag them to the correct position to precisely define your property on the map.',
        'button_back' => 'Back',
        'button_next' => 'Next',
    ],
    'area_confirm' => [
        'title' => 'Step 3: Almost done!',
        'title_alt' => 'Once you have marked your property, the check can begin.',
        'content' => 'Almost done: Click "Show result" when you are satisfied with your defined area. We check the conditions on your area at lightning speed and you receive the result of your area check immediately.',
        'button_back' => 'Back',
        'button_next' => 'Finish',
    ],
];

// Consent overlay
$GLOBALS['TL_LANG']['caeli_area_check']['consent']['overlay'] = [
    'title' => 'Consent required',
    'message' => 'For using this function we need your consent for Google Maps and HubSpot.',
    'button' => 'Give consent',
];

// Error messages
$GLOBALS['TL_LANG']['caeli_area_check']['error'] = [
    'no_geodata' => 'No geodata found for this location!',
    'geocoding_failed' => 'Geocoding was not successful for the following reason:',
    'select_area_first' => 'Please select an area first.',
    'google_maps_loading' => 'Google Maps API could not be loaded!',
    'title' => 'Error',
    'retry_button' => 'Try again',
];

// Alert messages for box display
$GLOBALS['TL_LANG']['caeli_area_check']['alerts'] = [
    'no_geodata' => [
        'title' => 'No geodata available',
        'message' => 'No geodata found for this location!',
        'type' => 'danger',
    ],
    'select_area_first' => [
        'title' => 'Select area',
        'message' => 'Please select an area first.',
        'type' => 'warning',
    ],
    'geocoding_failed' => [
        'title' => 'Geocoding failed',
        'message' => 'Geocoding was not successful for the following reason:',
        'type' => 'danger',
    ],
    'google_maps_loading' => [
        'title' => 'Google Maps Error',
        'message' => 'Google Maps API could not be loaded!',
        'type' => 'danger',
    ],
];

// Loading overlay
$GLOBALS['TL_LANG']['caeli_area_check']['loading']['title'] = 'Your property is being checked...';

// Interface elements
$GLOBALS['TL_LANG']['caeli_area_check']['interface'] = [
    'header' => [
        'title' => 'Caeli Wind Area Check',
        'subtitle' => 'for wind turbines',
    ],
    'form' => [
        'label' => 'Find and draw property:',
    ],
    'hints' => [
        'strong' => 'Note:',
        'warning' => 'Warning:',
    ],
]; 