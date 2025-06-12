<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
$config = array(
    'label' => array('Spalten Trenner: Ende', ''),
    'types' => array('content'),
    'contentCategory' => 'Spalten',
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'stop',
    ),
    'fields' => array(),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);