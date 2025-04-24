<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

//rsce_windpark_hero_config.php
return array(
    'label' => array('Custom | Hero', 'Zeigt einen Hero-Bereich an.'),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(

        'general_section_start' => array(
            'label' => ['Allgemeine Optionen'],
            'inputType' => 'group',
        ),

        'add_breadcrumb' => array(
            'label' => array('Breadcrumb hinzufügen', 'Fügt das angegebene Breadcrumb-Modul oberhalb der Headline ein.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr', 'submitOnChange' => true),
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
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),

        'headline' => array(
            'label' => array('Überschrift', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'mandatory' => true),
        ),

        'headline_type' => array(
            'label' => array('Typ der Überschrift', ''),
            'inputType' => 'select',
            'options' => array(
                'h1' => 'H1 (Haupt-Headline für SEO, darf nur 1x vorkommen)',
                'h2' => 'H2 (Sollte H1 thematisch untergeordnet sein)',
                'h3' => 'H3 (Sollte H2 thematisch untergeordnet sein)',
                'h4' => 'H4',
                'h5' => 'H5',
            ),
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

        'animation_list' => array(
            'label' => array('Animation: Aufzählung', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'clr w50')
        ),


        'list_items' => array(
            'label' => array('Aufzählungspunkte', 'Wird links neben dem Button angezeigt.'),
            'elementLabel' => '%s. Aufzählungspunkt',
            'inputType' => 'listWizard', // Standard Contao List-Widget
            'eval' => array('tl_class' => 'clr'),
            'fields' => array(
                'item_text' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true),
                ),
                'item_icon' => array(
                     'label' => array('Optional: Icon auswählen', 'Standard-Icon wird verwendet, wenn nichts ausgewählt ist.'),
                     'inputType' => 'fileTree',
                     'eval' => array(
                         'multiple' => false,
                         'fieldType' => 'radio',
                         'filesOnly' => true,
                         'extensions' => 'svg,png,jpg,jpeg,gif', // Passende Icon-Formate
                         'tl_class' => 'clr'
                     ),
                ),
            ),
        ),



        'buttons' => array(
            'label' => array('Buttons (rechts)', ''),
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
                'mandatory' => true,
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
    ),
);
