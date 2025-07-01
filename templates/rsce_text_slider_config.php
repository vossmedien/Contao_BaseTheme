<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;

return array(
    'label' => array('Custom | Headline & Text-Slider (text_slider)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'), // Globale Headline-Felder hier nicht als Standard, da sie spezifisch für die linke Spalte sind
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        // --- Optionen für das gesamte Element ---
        'animation_type_element' => array(
            'label' => array('Animation: Komplettes Element', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'clr'),
        ),

        // --- Linke Spalte: Headline ---
        'headline_section_start' => array(
            'label' => ['Linke Spalte: Überschrift & Text'],
            'inputType' => 'group',
        ),
        'topline' => array(
            'label' => array('Topline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'clr', 'allowHtml' => true),
        ),
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
        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'clr', 'allowHtml' => true),
        ),
        'headline_text' => array(
            'label' => array('Zusätzlicher Text (unter Headline)', 'Optionaler Textblock unter der Subline in der linken Spalte.'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),
        'headline_animation' => array(
            'label' => array('Animation (linke Spalte)', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'clr')
        ),
        'headline_column_css_class' => array(
            'label' => array('Zusätzliche CSS-Klasse für linke Spalte', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        // --- Rechte Spalte: Text-Slider ---
        'slider_section_start' => array(
            'label' => ['Rechte Spalte: Text-Slider Elemente'],
            'inputType' => 'group',
        ),
        'slider_items' => array(
            'label' => array('Slider-Elemente', ''),
            'elementLabel' => '%s. Slide',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 99,
            'fields' => array(
                'slide_image' => array(
                    'label' => array('Slide-Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => Contao\Config::get('validImageTypes'),
                        'tl_class' => 'w50',
                    ),
                ),
                'slide_image_no_lazy' => array(
                    'label' => array('Slide-Bild ohne Lazy-Loading laden', 'Deaktiviert das Lazy-Loading für das Slide-Bild'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'slide_image_size' => array(
                    'label' => array('Bildgröße', ''),
                    'inputType' => 'imageSize',
                    'options' => Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                        'tl_class' => 'w50'
                    ),
                ),
                'slide_topline' => array(
                    'label' => array('Topline im Slide', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'clr', 'allowHtml' => true),
                ),
                'slide_headline' => array(
                    'label' => array('Überschrift im Slide', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'slide_headline_type' => array(
                    'label' => array('Typ der Slide-Überschrift', ''),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getHeadlineTagOptions(),
                    'default' => 'h3',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'slide_subline' => array(
                    'label' => array('Subline im Slide', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'clr', 'allowHtml' => true),
                ),
                'slide_text' => array(
                    'label' => array('Text im Slide', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),
                'slide_buttons' => array(
                    'label' => array('Buttons im Slide', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 5,
                    'fields' => ButtonHelper::getButtonConfig(),
                ),
            ),
        ),
        'slider_column_css_class' => array(
            'label' => array('Zusätzliche CSS-Klasse für rechte Spalte (Slider)', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        // --- Slider-Konfiguration ---
        'slider_config_start' => array(
            'label' => ['Slider Konfiguration'],
            'inputType' => 'group',
        ),
        'slide_effect' => array(
            'label' => array('Slide-Effekt', ''),
            'inputType' => 'select',
            'options' => array('slide' => 'Slide (Standard)', 'fade' => 'Fade'), // Abgespeckt für Text-Slider
            'default' => 'slide',
            'eval' => array('tl_class' => 'w50'),
        ),
        'transition_time' => array(
            'label' => array('Animationszeit in ms', 'Standard: 1000'),
            'inputType' => 'text',
            'default' => '1000',
            'eval' => array('tl_class' => 'w50'),
        ),
        'show_pagination' => array(
            'label' => array('Paginierung anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'show_arrows' => array(
            'label' => array('Pfeile anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'loop' => array(
            'label' => array('Loop', 'Automatisch wieder von Anfang starten'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'autoplay' => array(
            'label' => array('Autoplay aktivieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'autoplay_time' => array(
            'label' => array('Autoplay-Zyklus in ms', 'Standard: 5000'),
            'inputType' => 'text',
            'default' => '5000',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array('field' => 'autoplay'),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config); 