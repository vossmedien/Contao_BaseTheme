<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
$config = array(
    'label' => array('Custom | Eigene Analytic-Scripts integrieren', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'standardFields' => array('headline', 'cssID'),
    'moduleCategory' => 'miscellaneous',
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

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);