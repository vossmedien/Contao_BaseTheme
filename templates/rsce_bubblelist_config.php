<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
$config = array(
    'label' => array('Custom | Runde Boxen mit Icon / Bild sowie Verlinkung (bubblelist)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'standardFields' => array('headline', 'cssID'),
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'topline' => array(
            'label' => array('Topline', 'Text oberhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ), 'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
             'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),
        'columns' => array(
            'label' => array('Maximale Spalten pro Zeile', 'Leer lassen für automatische Anpassung (auto-fit)'),
            'inputType' => 'select',
            'options' => array(
                '' => 'Dynamisch (Standard: 3 Spalten Desktop)',
                '1' => '1 Spalte',
                '2' => '2 Spalten',
                '3' => '3 Spalten',
                '4' => '4 Spalten',
                '5' => '5 Spalten',
                '6' => '6 Spalten',
            ),
            'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50')
        ),
        'backgroundcolor' => array(
            'label' => array('Hintergrundfarbe', 'Unterstützt Hex (#000), CSS-Variablen (var(--bs-light)) etc. (Standard: transparent)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'linkcolor' => array(
            'label' => array('Schriftfarbe', 'Unterstützt Hex (#000), CSS-Variablen (var(--bs-primary)) etc. (Standard: Hauptfarbe)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'default_bubble_color' => array(
            'label' => array('Standard-Farbe für Elemente', 'Wird verwendet, wenn keine individuelle Farbe gesetzt ist. Unterstützt Hex (#000), CSS-Variablen (var(--bs-primary)) etc.'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'default_hover_color' => array(
            'label' => array('Standard-Hover-Farbe für Elemente', 'Wird verwendet, wenn keine individuelle Hover-Farbe gesetzt ist. Unterstützt Hex (#000), CSS-Variablen (var(--bs-secondary)) etc.'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'hover_scale_percent' => array(
            'label' => array('Vergrößerung Bild/Icon bei Hover (in %)', 'z.B. 120 eingeben für 120%. Standard: 100'),
            'inputType' => 'text',
            'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50'),
        ),
        'hide_circle' => array(
            'label' => array('Runden Rahmen ausblenden', 'standardmäßig aktiv'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),
        'size' => array(
            'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => ' clr'
            ),
        ),
        'galery' => array(
            'label' => array('Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 99,
            'fields' => array(
                'content_type' => array(
                    'label' => array('Inhaltstyp auswählen', 'Was soll in der Bubble angezeigt werden?'),
                    'inputType' => 'radio',
                    'options' => array(
                        'image' => 'Bild',
                        'icon' => 'Icon (Font Awesome)',
                        'number' => 'Zahl'
                    ),
                    'eval' => array('mandatory' => true, 'tl_class' => 'clr'),
                ),
                'img' => array(
                    'label' => array('Bild/Icon', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => Contao\Config::get('validImageTypes'),
                        'tl_class' => 'w50',
                    ),
                    'dependsOn' => array(
                        'field' => 'content_type',
                        'value' => 'image',
                    ),
                ),
                'icon' => array(
                    'label' => array('Font-Awesome Klasse', 'z. B. fa-facebook fab'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'content_type',
                        'value' => 'icon',
                    ),
                ),
                'number' => array(
                    'label' => array('Zahl', 'Zahl die angezeigt werden soll'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'content_type',
                        'value' => 'number',
                    ),
                ),
                'img_no_lazy' => array(
                    'label' => array('Bild/Icon ohne Lazy-Loading laden', 'Deaktiviert das Lazy-Loading für das Bild/Icon'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'content_type',
                        'value' => 'image',
                    ),
                ),
                'size' => array(
                    'label' => array('Größe der Bubble (inkl. CSS-Einheit)', 'z.B. 100px oder 8rem. Ist immer quadratisch.'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'color' => array(
                    'label' => array('Alternative Farbe für Element', 'Überschreibt globale Standard-Farbe. Unterstützt Hex (#000), CSS-Variablen (var(--bs-primary)) etc.'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'hover_color' => array(
                    'label' => array('Alternative Hover-Farbe für Element', 'Überschreibt globale Standard-Hover-Farbe. Unterstützt Hex (#000), CSS-Variablen (var(--bs-secondary)) etc.'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'text' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),
                'url' => array(
                    'label' => array('URL', ''),
                    'inputType' => 'url',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);