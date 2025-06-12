<?php

use Contao\Config;
use Contao\System;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;

$config = [
    'label' => ['Custom | Content Slider mit Bildoptionen', 'Ein Slider mit Texten, optionaler Headline und flexiblen Bildoptionen (fixiert oder pro Slide).'],
    'types' => ['content'],
    'contentCategory' => 'Custom',
    'standardFields' => ['cssID'],
    'fields' => [
        // Element-Headline
        'element_headline_group' => [
            'label' => ['Element-Headline', ''],
            'inputType' => 'group',
        ],
        'headline' => [
            'label' => ['Headline', 'Optionale Haupt-Headline für das gesamte Element.'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
        ],
        'headline_type' => [
            'label' => ['Typ der Headline', ''],
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'eval' => ['tl_class' => 'w50 clr'],
            'default' => 'h2',
        ],

        // Inhalts-Container Klasse
        'content_container_class' => [
            'label' => ['Inhalts-Container-Klasse', 'optional'],
            'inputType' => 'text',
            'default' => '',
            'eval' => ['tl_class' => 'w50 clr'],
        ],

        // Bild Modus
        'image_settings_group' => [
            'label' => ['Bild-Einstellungen', ''],
            'inputType' => 'group',
        ],
        'image_mode' => [
            'label' => ['Bild-Anzeige-Modus', 'Wählen Sie, wie Bilder im Slider verwendet werden sollen.'],
            'inputType' => 'select',
            'options' => [
                'slide' => 'Jedes Slide hat ein eigenes Bild (rechts neben dem Text)',
                'fixed' => 'Ein fixiertes Bild für den gesamten Slider (rechts neben dem Text)',
                'none' => 'Keine Bilder anzeigen (Text-Slider nimmt volle Breite)',
            ],
            'eval' => [ 'tl_class' => 'w50 clr', 'mandatory' => true, 'includeBlankOption' => false],
            'default' => 'slide',
        ],

        // Fixiertes Bild (wenn image_mode === 'fixed')
        'fixed_image_group' => [
            'label' => ['Fixiertes Bild', 'Dieses Bild wird rechts neben allen Slides angezeigt.'],
            'inputType' => 'group',
            'dependsOn' => [
                'field' => 'image_mode',
                'value' => 'fixed',
            ],
        ],
        'fixed_image_src' => [
            'label' => ['Bildquelle', ''],
            'inputType' => 'fileTree',
            'eval' => [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => Config::get('validImageTypes'),
                'tl_class' => 'w50',
                'mandatory' => true,
            ],
            'dependsOn' => [
                'field' => 'image_mode',
                'value' => 'fixed',
            ],
        ],
        'fixed_image_size' => [
            'label' => ['Bildgröße', 'Siehe Backend Einstellungen -> Bildgrößen.'],
            'inputType' => 'imageSize',
            'options' => System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50 clr', 'rgxp' => 'digit'],
            'dependsOn' => [
                'field' => 'image_mode',
                'value' => 'fixed',
            ],
        ],


        // Slides
        'slides_group' => [
            'label' => ['Slides', 'Inhalte für die einzelnen Slides.'],
            'inputType' => 'group',
        ],
        'slides' => [
            'label' => ['Slides', ''],
            'elementLabel' => '%s. Slide Inhalt',
            'inputType' => 'list',
            'fields' => [
                'slide_image_src' => [
                    'label' => ['Bild für dieses Slide', '(Nur wenn "Jedes Slide hat ein eigenes Bild" gewählt wurde)'],
                    'inputType' => 'fileTree',
                    'eval' => [
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => Config::get('validImageTypes'),
                        'tl_class' => 'w50',
                    ],
                ],
                'slide_image_size' => [
                    'label' => ['Bildgröße für dieses Slide', ''],
                    'inputType' => 'imageSize',
                    'options' => System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50 clr', 'rgxp' => 'digit'],
                ],

                'slide_headline' => [
                    'label' => ['Slide-Headline', ''],
                    'inputType' => 'text',
                    'eval' => ['tl_class' => 'w50'],
                ],
                'slide_headline_type' => [
                    'label' => ['Typ der Slide-Headline', ''],
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getHeadlineTagOptions(),
                    'default' => 'h3',
                    'eval' => ['tl_class' => 'w50 clr'],
                ],
                'slide_content' => [
                    'label' => ['Text-Inhalt', ''],
                    'inputType' => 'textarea',
                    'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
                ],
                'buttons' => [
                    'label' => ['Buttons', ''],
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'fields' => ButtonHelper::getButtonConfig(),
                ],
            ],
        ],

        // Swiper Einstellungen
        'swiper_settings_group' => [
            'label' => ['Slider-Steuerung', ''],
            'inputType' => 'group',
        ],
        'loop' => [
            'label' => ['Loop-Modus', 'Slider startet nach dem letzten Slide von vorne.'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
        ],
        'autoplay' => [
            'label' => ['Autoplay', 'Slider wechselt automatisch die Slides.'],
            'inputType' => 'checkbox',
            'eval' => [ 'tl_class' => 'w50 clr m12'],
        ],
        'autoplay_time' => [
            'label' => ['Autoplay-Zeit (ms)', 'Verzögerung zwischen den Slides in Millisekunden. Standard: 5000'],
            'inputType' => 'text',
            'default' => '5000',
            'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
            'dependsOn' => [
                'field' => 'autoplay',
            ],
        ],
        'transition_time' => [
            'label' => ['Übergangszeit (ms)', 'Dauer des Slide-Wechsels in Millisekunden. Standard: 1000'],
            'inputType' => 'text',
            'default' => '1000',
            'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50 clr'],
        ],
    ],
];

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config); 