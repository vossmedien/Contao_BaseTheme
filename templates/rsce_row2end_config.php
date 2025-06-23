<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
$config = array(
    'label' => array('Spalten/Slider End-Element', ''),
    'types' => array('content'),
    'contentCategory' => 'Spalten',
    'standardFields' => array('headline', 'cssID'),
    'moduleCategory' => 'miscellaneous',
        'fields' => array(),
    'wrapper' => array(
        'type' => 'stop',
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);