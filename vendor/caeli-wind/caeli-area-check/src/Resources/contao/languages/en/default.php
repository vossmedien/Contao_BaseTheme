<?php

declare(strict_types=1);

/*
 * Caeli Area Check Bundle - English translations
 */

// Loading texts
$GLOBALS['TL_LANG']['caeli_area_check']['loading']['texts'] = [
     'checking_area' => 'Analyzing your land area',
    'wind_conditions' => 'Are the wind conditions suitable?',
    'restrictions_check' => 'Are there any planning restrictions?',
    'grid_connection' => 'Evaluating grid connection options',
    'analyzing_potential' => 'Analyzing wind potential',
    'checking_nature' => 'Initial faunistic assessment',
    'checking_distances' => 'Reviewing distance requirements',
    'evaluating_quality' => 'Evaluating site quality',
];

// Form elements
$GLOBALS['TL_LANG']['caeli_area_check']['form'] = [
    'address_label' => 'Address or Location',
    'address_placeholder' => 'Enter location or postal code',
    'address_alert' => 'Please enter a complete address or postal code.',
    'name_label' => 'Last Name',
    'firstname_label' => 'First Name',
    'phone_label' => 'Phone',
    'email_label' => 'Email Address',
    'submit_button' => 'Start Area Check',
    'button' => [
        'check_area' => 'Confirm selected area',
        'restart' => 'Restart',
    ],
    'warning' => 'The selected area is larger than 700 hectares. Please reduce the area size.',
];

// Tutorial system
$GLOBALS['TL_LANG']['caeli_area_check']['tutorial'] = [
    'welcome' => [
        'title' => 'Welcome to your site check.',
        'content' => 'Determine the wind potential of your property in two steps. Click "Start" to begin.',
        'button_skip' => 'Skip',
        'button_next' => 'Start',
    ],
    'plz_input' => [
        'title' => 'Step 1: Find property.',
        'button_back' => 'Back',
        'button_next' => 'Continue',
        'content' => 'Enter the address of your property and select the suggested location.',
    ],
    'polygon_edit' => [
        'title' => 'Step 2: Draw and check property.',
        'content' => 'You now see a yellow polygon that you can move to the desired position. Use the corner points of the polygon to roughly outline your property and select "Show result" when you are finished. We check the conditions of your area at lightning speed.',
        'button_back' => 'Back',
        'button_next' => 'Continue',
    ],
    'area_confirm' => [
        'title' => 'Step 3: Almost done!',
        'content' => 'Almost done: Move the polygon to the correct position and align the corner points to your property. Then click "Show result". We check the conditions on your area at lightning speed.',
        'button_back' => 'Back',
        'button_next' => 'Finish',
    ],
];

// Consent overlay
$GLOBALS['TL_LANG']['caeli_area_check']['consent']['overlay'] = [
    'title' => 'Your site check<br>starts soon!',
    'message' => 'To show you the interactive map and process your request, we need your consent for necessary cookies. You can revoke your consent at any time via the <a href="#" onclick="var recall = document.querySelector(\'.cmpboxrecalllink\'); if(recall) recall.click(); setTimeout(function(){var tab = document.querySelector(\'[data-cmp-purpose=c55]\'); if(tab) tab.click();}, 100); return false;">consent manager</a>.',
    'button' => 'Start now',
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
    'invalid_postal_code' => [
        'title' => 'Invalid Postal Code',
        'message' => 'Please enter a valid address with postal code.',
        'type' => 'warning',
    ],
    'ajax_fallback' => [
        'title' => 'Processing Running',
        'message' => 'AJAX processing not available.',
        'type' => 'info',
    ],
    'processing_error' => [
        'title' => 'Processing Error',
        'message' => 'An error occurred during site check:',
        'type' => 'danger',
    ],
    'fallback_sync' => [
        'title' => 'Switching to Standard Processing',
        'message' => 'The check continues with standard processing...',
        'type' => 'info',
    ],
];

// Loading overlay
$GLOBALS['TL_LANG']['caeli_area_check']['loading'] = [
    'title' => 'Your site is being checked...',
    'progress' => 'Progress',
    'texts' => [
        'checking_area' => 'We are checking your area',
        'wind_conditions' => 'Do the wind conditions match?',
        'restrictions_check' => 'Are there restrictions?',
        'grid_connection' => 'Is grid connection available?',
        'analyzing_potential' => 'Analyzing wind potential',
        'checking_nature' => 'Checking nature reserves',
        'calculating_economics' => 'Calculating economics',
        'checking_distances' => 'Checking distance regulations',
        'analyzing_capacity' => 'Analyzing grid capacity',
        'evaluating_quality' => 'Evaluating site quality'
    ],
    'steps' => [
        'connecting' => 'Connecting to API...',
        'analyzing' => 'Area is being analyzed...',
        'rating' => 'Wind potential is being evaluated...',
        'fallback_rating' => 'Alternative evaluation is being created...',
        'saving' => 'Result is being saved...',
        'completed' => 'Processing completed!'
    ],
    'completed_redirect' => 'Completed! Redirecting...',
    'please_wait' => 'Please be patient'
];

// Interface elements
$GLOBALS['TL_LANG']['caeli_area_check']['interface'] = [
    'header' => [
        'title' => 'Caeli Wind Site Check',
        'subtitle' => 'for Wind Energy Projects',
    ],
    'form' => [
        'label' => 'Find and mark your land',
    ],
    'hints' => [
        'strong' => 'Note:',
        'warning' => 'Warning:',
    ],
    'no_plz_message' => 'Enter location or postal code',
];

// Result page translations
$GLOBALS['TL_LANG']['caeli_area_check']['result'] = [
    'success' => [
        'title_bold' => 'Preliminary result',
        'title_text' => 'Your property has wind power potential',
    ],
    'unsuitable' => [
        'title_bold' => 'Preliminary result:',
        'title_text' => "The area doesn't seem ideal - let's check that",
    ],
    'criteria' => [
        'wind_conditions' => [
            'title' => 'Wind Conditions',
            'description' => 'The wind power in the specified area.',
            'rating' => [
                'green' => 'Well suited',
                'yellow' => 'Conditionally suitable',
                'red' => 'Not suitable',
            ],
        ],
        'restrictions' => [
            'title' => 'Restrictions',
            'description' => 'Examination of spatial planning restrictions on which wind power utilisation is prohibited.',
            'rating' => [
                'green' => 'No restrictions',
                'yellow' => 'Limited possible',
                'red' => 'Restrictions present',
            ],
        ],
        'grid_connection' => [
            'title' => 'Power',
            'description' => 'Accessibility of the plot area to the nearest high voltage.',
            'rating' => [
                'green' => 'Well accessible',
                'yellow' => 'Conditionally accessible',
                'red' => 'Not suitable',
            ],
        ],
    ],
    'conclusion' => [
        'title' => 'Conclusion:',
        'unsuitable_text' => 'Your area does not meet all criteria for an optimal wind energy project.',
        'good_wind_text' => 'However, wind conditions are promising.',
        'contact_text' => 'Contact us for individual consultation.',
    ],
    'form_section' => [
        'suitable_title' => 'Get your free non-binding initial assessment now:',
        'unsuitable_title' => 'Let us analyze your area in detail:',
        'pdf_report_button' => 'Get Your Free PDF Report',
    ],
    'badges' => [
        'pdf_suitable' => '/files/base/layout/img/caeli_de/badges/flaechencheck-pdf-badge-en.webp',
        'pdf_unsuitable' => '/files/base/layout/img/caeli_de/badges/flaechencheck-pdf-badge-en_negativ.svg',
    ],
    'error_states' => [
        'check_failed' => [
            'title' => 'Evaluation Error',
            'button' => 'Start New Check',
        ],
        'area_unsuitable' => [
            'title' => 'Your Area Check Result',
            'warning_title' => 'Area not suitable',
            'warning_text' => 'Unfortunately, your selected area is not suitable for a wind energy project.',
            'reason_label' => 'Reason:',
        ],
        'checked_area' => [
            'title' => 'Checked Area',
            'address_label' => 'Checked Address:',
            'timestamp_label' => 'Check performed:',
            'not_available' => 'Not available',
        ],
        'what_to_do' => [
            'title' => 'What can you do?',
            'try_other_area' => 'Try another area',
            'contact_advice' => 'Contact us for individual consultation',
            'alternative_locations' => 'Learn about alternative locations',
        ],
        'not_found' => [
            'title' => 'Error',
            'message' => 'The requested area check could not be found.',
            'button' => 'Start New Check',
        ],
        'welcome' => [
            'title' => 'Welcome to Area Check',
            'message' => 'Please first perform an site check.',
            'button' => 'Start Area Check',
        ],
    ],
    'buttons' => [
        'new_check' => 'Check New Area',
        'request_consultation' => 'Request Consultation',
    ],
];

// Status legend for area progress
$GLOBALS['TL_LANG']['caeli_area_check']['status'] = [
    'legend' => [
        'title' => 'Legend',
        'completed' => 'Completed',
        'in_progress' => 'In Progress',
        'requested' => 'Requested',
    ],
]; 