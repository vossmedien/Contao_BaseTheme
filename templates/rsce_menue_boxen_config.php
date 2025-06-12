<?php

use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;

$config = array(
    'label' => array('Custom | Menü-Boxen', 'Boxen mit Verlinkungen für Menü-Strukturen'),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'standardFields' => array('cssID'),
    'fields' => array(

        'headline' => array(
            'label' => array('Überschrift', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'headline_type' => array(
            'label' => array('Typ der Überschrift', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'default' => 'h2',
            'eval' => array('tl_class' => 'w50'),
        ),

        'intro_text' => array(
            'label' => array('Einleitungstext', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),

        'boxes' => array(
            'label' => array('Menü-Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 12,
            'fields' => array(
                'box_title' => array(
                    'label' => array('Box-Titel', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'box_icon' => array(
                    'label' => array('Icon-Klasse', 'z.B. fa-home für FontAwesome Icons'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'box_description' => array(
                    'label' => array('Beschreibung', ''),
                    'inputType' => 'textarea',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'box_link' => array(
                    'label' => array('Link', 'Link-Ziel für die Box'),
                    'inputType' => 'pageTree',
                    'eval' => array('fieldType' => 'radio', 'tl_class' => 'w50'),
                ),
                'box_link_target' => array(
                    'label' => array('In neuem Fenster öffnen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
        ),

        'columns' => array(
            'label' => array('Anzahl Spalten', 'Wie viele Boxen pro Zeile angezeigt werden sollen'),
            'inputType' => 'select',
            'options' => array(
                '1' => '1 Spalte',
                '2' => '2 Spalten',
                '3' => '3 Spalten',
                '4' => '4 Spalten',
                '6' => '6 Spalten'
            ),
            'default' => '3',
            'eval' => array('tl_class' => 'w50'),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config); 