<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
$config = array(
    'label' => array('Spalten Trenner: Start', ''),
    'types' => array('content'),
    'contentCategory' => 'Spalten',
    'standardFields' => array('headline', 'cssID'),
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'start',
    ),
    'fields' => array(
        'background_color' => array(
            'label' => array('Spalten-Hintergrundfarbe', 'In HEX oder rgb(a) angeben, funktioniert bei 2 Spalten!'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);