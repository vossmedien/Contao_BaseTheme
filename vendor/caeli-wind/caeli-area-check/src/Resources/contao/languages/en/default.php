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
    'address_label' => 'Address or Location',
    'address_placeholder' => 'Enter address or postal code',
    'address_alert' => 'Please enter a complete address or postal code.',
    'name_label' => 'Last Name',
    'firstname_label' => 'First Name',
    'phone_label' => 'Phone',
    'email_label' => 'Email Address',
    'submit_button' => 'Start Area Check',
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
    'title' => 'Your area check starts soon!',
    'message' => 'To show you the interactive map and process your request, we need your brief consent.',
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
        'message' => 'An error occurred during area check:',
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
    'title' => 'Your property is being checked...',
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
    ]
];

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

// Result page translations
$GLOBALS['TL_LANG']['caeli_area_check']['result'] = [
    'success' => [
        'title_bold' => 'Congratulations:',
        'title_text' => 'Your property shows good conditions for wind power.',
    ],
    'unsuitable' => [
        'title_bold' => 'Area Check Result:',
        'title_text' => 'Your area is not optimally suited for wind power.',
    ],
    'criteria' => [
        'wind_conditions' => [
            'title' => 'Wind Conditions',
            'description' => 'Wind power performance in the specified area',
            'rating' => [
                'green' => 'Well suited',
                'yellow' => 'Conditionally suitable',
                'red' => 'Not suitable',
            ],
        ],
        'restrictions' => [
            'title' => 'Restrictions',
            'description' => 'Review of spatial planning restrictions where wind energy use is prohibited.',
            'rating' => [
                'green' => 'No restrictions',
                'yellow' => 'Limited possible',
                'red' => 'Restrictions present',
            ],
        ],
        'grid_connection' => [
            'title' => 'Grid Connection',
            'description' => 'Accessibility of the property area to the nearest high voltage.',
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
        'suitable_title' => 'Check your wind power opportunity now – free and non-binding:',
        'unsuitable_title' => 'Interested in detailed consultation?',
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
            'message' => 'Please first perform an area check.',
            'button' => 'Start Area Check',
        ],
    ],
    'buttons' => [
        'new_check' => 'Check New Area',
        'request_consultation' => 'Request Consultation',
    ],
]; 