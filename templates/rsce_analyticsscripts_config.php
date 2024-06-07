<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Eigene Analytic-Scripts integrieren', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        
        'ga_id' => array(
            'label' => array('Google Analytics ID', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => ''),
        ),


        'tiktok_id' => array(
            'label' => array('TikTok ID', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => ''),
        ),

    ),
);
