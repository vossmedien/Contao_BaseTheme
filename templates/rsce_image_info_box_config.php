<?php

use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;

$config = [
    'label' => array('Custom | Bild mit Info-Box (image_info_box)', 'Zeigt ein Bild oberhalb einer Box mit zwei Informationsspalten an.'),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'image_section' => array(
            'label' => ['Bild Optionen'],
            'inputType' => 'group',
        ),
        'image' => array(
            'label' => array('Bild auswählen', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => \Contao\Config::get('validImageTypes'),
                'tl_class' => 'clr'
            ),
        ),
        'image_size' => array(
            'label' => array('Bildgröße', ''),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'w50'
            ),
        ),
        'box_section' => array(
            'label' => ['Info-Box Optionen'],
            'inputType' => 'group',
        ),
        'col1_title' => array(
            'label' => array('Spalte 1: Titel', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'col1_label' => array(
            'label' => array('Spalte 1: Text/Label', ''),
            'inputType' => 'text',
             'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'col2_title' => array(
            'label' => array('Spalte 2: Titel', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'col2_label' => array(
            'label' => array('Spalte 2: Text/Label', ''),
            'inputType' => 'text',
             'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),

        'layout_section' => array(
            'label' => ['Layout & Design'],
            'inputType' => 'group',
        ),
         'box_css_class' => array(
            'label' => array('Zusätzliche CSS-Klasse(n) für die Box', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'box_animation' => array(
            'label' => array('Animation für die Box', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50', 'includeBlankOption' => true),
        ),

    ),
];

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config); 