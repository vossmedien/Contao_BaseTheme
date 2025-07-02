<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;

return [
    'label' => ['Custom | Hero Split (Box / Boxen)', 'Zweispaltiges Element mit Box links und Boxen rechts.'],
    'types' => ['content'],
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => [
        'type' => 'none',
    ],
    'fields' => [
        // Globale Einstellungen
        'global_settings' => [
            'label' => ['Globale Einstellungen'],
            'inputType' => 'group',
        ],
        'animation_type_top_headline' => [
            'label' => ['Animation: Obere Headline', ''],
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => ['tl_class' => 'w50 clr']
        ],
        'onlystyle_top_headline' => [
            'label' => ['Obere Headline: Nur als Überschrift darstellen', ''],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
        ],

        'css_class_headline' => [
            'label' => ['CSS-Klasse (Obere Headline)', 'Zusätzliche CSS-Klasse für die obere Headline'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50']
        ],

        // Linke Spalte
        'left_column' => [
            'label' => ['Linke Spalte'],
            'inputType' => 'group',
        ],
        'top_topline' => [
            'label' => ['Obere Topline', ''],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50 clr', 'allowHtml' => true],
        ],
        'top_headline' => [
            'label' => ['Obere Überschrift', ''],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
        ],
        'top_headline_type' => [
            'label' => ['Typ der oberen Überschrift', ''],
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'default' => 'h1',
            'eval' => ['tl_class' => 'w50 clr'],
        ],
        'top_subline' => [
            'label' => ['Obere Subline', ''],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'allowHtml' => true],
        ],

        'left_box' => [
            'label' => ['Linke Box', 'Die Box, die links angezeigt wird.'],
            'elementLabel' => 'Linke Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 1,
            'eval' => ['tl_class' => 'clr'],
            'fields' => [
                'animation_type' => [
                    'label' => [
                        'de' => ['Art der Einblendeanimation', ''],
                    ],
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => ['chosen' => 'true', 'tl_class' => 'w50']
                ],
                'image' => [
                    'label' => ['Bild', ''],
                    'inputType' => 'fileTree',
                    'eval' => [
                        'filesOnly' => true,
                        'extensions' => \Contao\Config::get('validImageTypes'),
                        'fieldType' => 'radio',
                        'mandatory' => true,
                        'tl_class' => 'clr'
                    ],
                ],
                'image_no_lazy' => [
                    'label' => ['Bild ohne Lazy-Loading laden', 'Deaktiviert das Lazy-Loading für das Bild'],
                    'inputType' => 'checkbox',
                    'eval' => ['tl_class' => 'w50'],
                ],
                'image_size' => [
                    'label' => ['Bildgröße', 'Optional. Wenn leer, wird Originalbild verwendet.'],
                    'inputType' => 'imageSize',
                    'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => [
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                        'tl_class' => 'w50'
                    ],
                ],
                'title' => [
                    'label' => ['Titel', 'Text in der Box'],
                    'inputType' => 'text',
                    'eval' => ['mandatory' => true, 'tl_class' => 'w50 clr'],
                ],
                'link' => [
                    'label' => ['Verlinkung', 'Interne Seite auswählen'],
                    'inputType' => 'pageTree',
                    'eval' => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'w50'],
                ],
            ],
        ],

        'bottom_headline_section' => [
            'label' => ['Untere Headline (Links)'],
            'inputType' => 'group',
        ],
        'bottom_topline' => [
            'label' => ['Untere Topline', ''],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50 clr', 'allowHtml' => true],
        ],
        'bottom_headline' => [
            'label' => ['Untere Überschrift', ''],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
        ],
        'bottom_headline_type' => [
            'label' => ['Typ der unteren Überschrift', ''],
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'default' => 'h2',
            'eval' => ['tl_class' => 'w50 clr'],
        ],
        'bottom_subline' => [
            'label' => ['Untere Subline', ''],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'allowHtml' => true],
        ],
        'animation_type_bottom_headline' => [
            'label' => ['Animation: Untere Headline', ''],
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => ['tl_class' => 'w50 clr']
        ],
        'left_bottom_headline_css_class' => [
            'label' => ['CSS-Klasse (Headline Bereich)', 'Zusätzliche CSS-Klasse für den Bereich der unteren Headline links'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50']
        ],
        'left_text' => [
            'label' => ['Text (Links, unter Headline)', 'Optional'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
        ],
        'animation_type_left_text' => [
            'label' => ['Animation: Text Links', ''],
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => ['tl_class' => 'w50 clr']
        ],

        // Rechte Spalte
        'right_column' => [
            'label' => ['Rechte Spalte (Boxen & Inhalt unten)'],
            'inputType' => 'group',
        ],
        'background_color' => [
            'label' => ['Hintergrundfarbe der Boxen', 'Standard: #ECF0F2 mit 90% Opazität'],
            'inputType' => 'text',
            'default' => 'rgba(236, 240, 242, 0.9)',
            'eval' => ['colorpicker' => true, 'tl_class' => 'w50 clr'],
        ],
        'text_color' => [
            'label' => ['Textfarbe der Boxen', 'Standard: var(--bs-body-color)'],
            'inputType' => 'text',
            'default' => 'var(--bs-body-color)',
            'eval' => ['colorpicker' => true, 'tl_class' => 'w50'],
        ],
        'font_size' => [
            'label' => ['Schriftgröße der Boxen', 'Standard: 20px'],
            'inputType' => 'text',
            'default' => '20px',
            'eval' => ['tl_class' => 'w50 clr'],
        ],
        'boxes' => [
            'label' => ['Rechte Boxen', ''],
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'eval' => ['tl_class' => 'clr'],
            'fields' => [
                'animation_type' => [
                    'label' => [
                        'de' => ['Art der Einblendeanimation', ''],
                    ],
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => ['chosen' => 'true', 'tl_class' => 'w50']
                ],
                'image' => [
                    'label' => ['Bild', ''],
                    'inputType' => 'fileTree',
                    'eval' => [
                        'filesOnly' => true,
                        'extensions' => \Contao\Config::get('validImageTypes'),
                        'fieldType' => 'radio',
                        'mandatory' => true,
                        'tl_class' => 'clr'
                    ],
                ],
                'image_no_lazy' => [
                    'label' => ['Bild ohne Lazy-Loading laden', 'Deaktiviert das Lazy-Loading für das Bild'],
                    'inputType' => 'checkbox',
                    'eval' => ['tl_class' => 'w50'],
                ],
                'image_size' => [
                    'label' => ['Bildgröße', 'Optional. Wenn leer, wird Originalbild verwendet.'],
                    'inputType' => 'imageSize',
                    'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => [
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                        'tl_class' => 'w50'
                    ],
                ],
                'title' => [
                    'label' => ['Titel', 'Text in der Box'],
                    'inputType' => 'text',
                    'eval' => ['mandatory' => true, 'tl_class' => 'w50 clr'],
                ],
                'link' => [
                    'label' => ['Verlinkung', 'Interne Seite auswählen'],
                    'inputType' => 'pageTree',
                    'eval' => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'w50'],
                ],
                'column_class' => [
                    'label' => ['Breite der Box/Gruppe (Bootstrap Grid)', 'Wählen Sie die Spaltenbreite für diese Box oder Gruppe.'],
                    'inputType' => 'select',
                    'options' => [
                        'col-12' => 'Ganze Breite (12/12)',
                        'col-lg-8' => 'Zwei Drittel (8/12)',
                        'col-lg-6' => 'Halbe Breite (6/12)',
                        'col-lg-4' => 'Ein Drittel (4/12)',
                        'col-lg-3' => 'Ein Viertel (3/12)',
                    ],
                    'default' => 'col-12',
                    'eval' => ['tl_class' => 'w50 clr'],
                ],
                'vertical_stack_group' => [
                    'label' => ['Vertikale Stapelgruppe', 'Optional. Geben Sie eine Zahl ein (z.B. 1, 2). Boxen mit der gleichen Zahl werden vertikal gestapelt und teilen sich die definierte Spaltenbreite.'],
                    'inputType' => 'text',
                    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
                ],
            ],
        ],

        'bottom_text' => [
            'label' => ['Text (Rechts, unter den Boxen)', 'Optional'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
        ],
        'animation_type_bottom_text' => [
            'label' => ['Animation: Unterer Text Rechts', ''],
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => ['tl_class' => 'w50 clr']
        ],
        'buttons' => [
            'label' => ['Buttons (Rechts, unter dem Text)', ''],
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 20,
            'eval' => ['tl_class' => 'clr'],
            'fields' => ButtonHelper::getButtonConfig(),
        ],
        'right_bottom_content_css_class' => [
            'label' => ['CSS-Klasse (Inhalt unten)', 'Zusätzliche CSS-Klasse für den Bereich von Text und Buttons unten rechts'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50']
        ],
    ],
];

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config); 