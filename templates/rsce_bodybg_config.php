<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
$config = array(
    'label' => array('Custom | Hintergrund für Website / Artikel / Element (bodybg)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(

        'image' => array(
            'label' => array('Bild / Video', ""),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4,svg,webp',
                'tl_class' => 'clr'
            ),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '2',
            ),
        ),

        'image_mobile' => array(
            'label' => array('Alternative für Mobile', ""),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4,svg,webp',
            ),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '2',
            ),
        ),

        'settings' => array(
            'label' => array('Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('collapsible' => true, 'collapsed' => true),
        ),

        'element_type' => array(
            'label' => array('Darstellungstyp', ''),
            'inputType' => 'radio',
            'options' => array(
                '1' => 'Slider',
                '2' => 'Einzelnes Bild / Video',
                '3' => 'Eigenes CSS (z. B. für Gradient / Verlauf)',
            ),
        ),

        'target_type' => array(
            'label' => array('Ziel auswählen', ''),
            'inputType' => 'radio',
            'options' => array(
                'body' => 'Kompletter Body',
                'article' => 'Nur der aktuelle Abschnitt/Artikel',
                'element' => 'Bestimmtes Element (nach ID)',
            ),
            'eval' => array('tl_class' => 'clr'),
        ),

        'element_id' => array(
            'label' => array('Element-ID', 'Geben Sie die ID des Elements ein (z.B. "my-section" oder "#my-section")'),
            'inputType' => 'text',
            'eval' => array(
                'maxlength' => 100,
                'tl_class' => 'w50',
                'placeholder' => 'z.B. my-section'
            ),
            'dependsOn' => array(
                'field' => 'target_type',
                'value' => 'element',
            ),
        ),

        'only_article' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Aktiviert (durch target_type bereits gesetzt)',
            ),
            'eval' => array('readonly' => true),
            'dependsOn' => array(
                'field' => 'target_type',
                'value' => 'article',
            ),
        ),

        'is_between' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Abstand zum Artikel davor entfernen, so dass Abschrägungen ggf. zusammengeführt werden und Artikel aneinander liegen',
            ),
            'dependsOn' => array(
                'field' => 'target_type',
                'value' => array('article', 'element'),
            ),
        ),

        'multiSRC' => array(
            'inputType' => 'standardField',
            'eval' => array(
                'multiple' => true,
                'fieldType' => 'checkbox',
                'orderField' => 'orderSRC',
                'files' => true,
                'mandatory' => false,
                'isGallery' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
            ),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '1',
            ),
        ),

        'slide_effect' => array(
            'label' => array(
                'de' => array('Slide-Effekt', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'slide' => 'Slide (Standard)',
                'fade' => 'Fade',
                'coverflow' => 'Coverflow',
                'flip' => 'Flip',
                'cube' => 'Cube',
            ),
            'eval' => array('tl_class' => ''),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '1',
            ),
        ),

        'darken_image' => array(
            'label' => array('', ''),
            'inputType' => 'radio',
            'eval' => array('tl_class' => ''),
            'options' => array(
                '1' => 'Hintergrundbild zusätzlich abdunkeln',
                '2' => 'Hintergrundbild zusätzlich erhellen',
            ),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '2',
            ),
        ),

        'autoplay' => array(
            'label' => array('Autoplay aktivieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ''),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '1',
            ),
        ),

        'autoplay_time' => array(
            'label' => array('Autoplay-Zyklus', 'nach wie viel MS soll zum nächsten Slide gewechselt werden, Standard: 7500'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'autoplay',
            ),
            'eval' => array('tl_class' => ''),
        ),

        'transition_time' => array(
            'label' => array('Animationszeit in ms', 'Standard: 1500'),
            'inputType' => 'text',
            'eval' => array('tl_class' => ''),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '1',
            ),
        ),

        'fit_image' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Bild auf Breite und Höhe des Bereichs strecken',
            ),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '2',
            ),
        ),

        'css' => array(
            'label' => array('Eigener Code', 'wird als inline-style innerhalb von "background: #WERT#" eingebunden. Falls ausgefüllt, wird dieser Wert auch für die Abschrägungen genutzt.'),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '3',
            ),
        ),

        'activate' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Abschrägung aktivieren. Wichtig: deaktiviert parallax-Effekt',
            ),
            'dependsOn' => array(
                'field' => 'target_type',
                'value' => array('article', 'element'),
            ),
        ),

        'winkel' => array(
            'label' => array('Abschrägungswinkel', 'Geben Sie einen Wert zwischen -5 und 5 ein. Standard: 2.5 bzw. Inhalt von var(--base-skew)'),
            'inputType' => 'text',
            'eval' => array(
                'rgxp' => 'digit',
                'maxlength' => 2,
                'tl_class' => 'w50'
            ),
            'sql' => "varchar(2) NOT NULL default ''",
            'dependsOn' => array(
                'field' => 'activate',
                'value' => '1',
            ),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);