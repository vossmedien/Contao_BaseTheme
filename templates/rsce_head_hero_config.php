<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;

//rsce_windpark_hero_config.php
$config = array(
    'label' => array('Custom | Hero', 'Zeigt einen Hero-Bereich an.'),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(

        'general_section_start' => array(
            'label' => ['Allgemeine Optionen'],
            'inputType' => 'group',
        ),

        'container_css_class' => array(
            'label' => array('Container CSS-Klasse', 'Optionale CSS-Klasse für einen inneren Wrapper um den gesamten Inhalt des Elements.'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'add_breadcrumb' => array(
            'label' => array('Breadcrumb hinzufügen', 'Fügt das angegebene Breadcrumb-Modul oberhalb der Headline ein.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'breadcrumb_module' => array(
            'label' => array('Breadcrumb Modul ID', 'Die ID des Contao Moduls, das eingefügt werden soll.'),
            'inputType' => 'text',
            'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'add_breadcrumb',
            ),
        ),

        'headline_section_start' => array(
            'label' => ['Headline & Text'],
            'inputType' => 'group',
        ),

        'animation_headline' => array(
            'label' => array('Animation: Headline & Text', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'clr')
        ),

        'topline' => array(
            'label' => array('Topline', ''),
            'inputType' => 'text',
              'eval' => array('tl_class' => ' clr', 'allowHtml' => true),
        ),

        'headline' => array(
            'label' => array('Überschrift', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'mandatory' => true),
        ),

        'headline_type' => array(
            'label' => array('Typ der Überschrift', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'default' => 'h1',
            'eval' => array('tl_class' => 'w50'),
        ),

        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'clr', 'allowHtml' => true),
        ),

        'text' => array(
            'label' => array('Text unterhalb der Headline', 'Wird im Frontend mit max. col-xl-10 Breite dargestellt.'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),

        'content_section_start' => array(
            'label' => ['Aufzählung & Button'],
            'inputType' => 'group',
        ),

        'add_list_items' => array(
            'label' => array('Aufzählungspunkte hinzufügen', 'Zeigt die Eingabemöglichkeit für Aufzählungspunkte an.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'list_full_width' => array(
            'label' => array('Aufzählungspunkte in der vollen Breite anzeigen', 'Wenn aktiviert, nehmen die Aufzählungspunkte die volle verfügbare Breite ein (col-12).'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'add_list_items',
            ),
        ),

        'animation_list' => array(
            'label' => array('Animation: Aufzählung', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'clr w50'),
                 'dependsOn' => array(
                'field' => 'add_list_items',
            ),
        ),

        'list_items' => array(
            'label' => array('Aufzählungspunkte', 'Wird links neben dem Button angezeigt.'),
            'elementLabel' => '%s. Aufzählungspunkt',
            'inputType' => 'listWizard', // Standard Contao List-Widget
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'add_list_items',
            ),
            'minItems' => 0, // Erlaube eine leere Liste
            'fields' => array(
                'item_text' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'decodeEntities' => true, 'tl_class' => 'long'), // HTML erlauben und Entities dekodieren
                ),
            ),
        ),

        'buttons' => array(
            'label' => array('Buttons', ''),
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 5, // Beispiel: Max. 5 Buttons
            'eval' => array('tl_class' => 'clr'),
            'fields' => ButtonHelper::getButtonConfig(), // Standard Button Konfiguration
        ),

        'image_section_start' => array(
            'label' => ['Bild (Unterhalb)'],
            'inputType' => 'group',
        ),

        'animation_image' => array(
            'label' => array('Animation: Bild', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'clr')
        ),

        'image_src' => array(
            'label' => array('Bild auswählen', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => \Contao\Config::get('validImageTypes'),
                'mandatory' => false,
                'tl_class' => 'clr'
            ),
        ),
        'image_no_lazy' => array(
            'label' => array('Bild ohne Lazy-Loading laden', 'Deaktiviert das Lazy-Loading für das Bild'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
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
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);
