<?php


use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

$config = [
    'label' => ['Custom | Job Teaser', 'Erstellt einen Teaser für Stellenangebote'],
    'types' => ['content'],
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => [
        'type' => 'none',
    ],
    'fields' => [
        'topline' => [
            'label' => ['Topline', 'Text oberhalb der Überschrift'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
        ],
        'subline' => [
            'label' => ['Subline', 'Text unterhalb der Überschrift'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
        ],
        'text_color' => [
            'label' => ['Schriftfarbe', 'Wählen Sie die Schriftfarbe (Hexadezimalwert oder CSS-Variable)'],
            'inputType' => 'text',
            'eval' => [// Erhöht, um längere CSS-Variablen zu ermöglichen
                'tl_class' => 'w50',
                'allowHtml' => true,  // Erlaubt die Eingabe von CSS-Variablen
            ],
        ],
        'background_type' => [
            'label' => ['Hintergrundtyp', 'Wählen Sie zwischen Hintergrundbild und Hintergrundfarbe'],
            'inputType' => 'radio',
            'options' => ['image' => 'Hintergrundbild', 'color' => 'Hintergrundfarbe'],
            'eval' => ['mandatory' => true],
        ],
        'background_image' => [
            'label' => ['Hintergrundbild', 'Wählen Sie ein Hintergrundbild aus'],
            'inputType' => 'fileTree',
            'eval' => [
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg',
                'fieldType' => 'radio',
                'tl_class' => 'clr',
            ],
            'dependsOn' => [
                'field' => 'background_type',
                'value' => 'image',
            ],
        ],
        'background_image_size' => [
            'label' => ['Hintergrundbildgröße', 'Hier können Sie die Abmessungen des Hintergrundbildes festlegen.'],
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => [
                'rgxp' => 'natural',
                'includeBlankOption' => true,
                'nospace' => true,
                'helpwizard' => true,
                'tl_class' => 'w50',
            ],
            'dependsOn' => [
                'field' => 'background_type',
                'value' => 'image',
            ],
        ],
        'background_color' => [
            'label' => ['Hintergrundfarbe', 'Wählen Sie die Hintergrundfarbe (Hexadezimalwert oder CSS-Variable)'],
            'inputType' => 'text',
            'eval' => [
                'tl_class' => 'w50',
                'allowHtml' => true,
            ],
            'dependsOn' => [
                'field' => 'background_type',
                'value' => 'color',
            ],
        ],
        'animation_type' => [
            'label' => ['Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'],
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => ['chosen' => 'true', 'tl_class' => 'w50'],
        ],
        'image' => [
            'label' => ['Bild', 'Wählen Sie ein Bild aus'],
            'inputType' => 'fileTree',
            'eval' => [
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg',
                'fieldType' => 'radio',
                'tl_class' => 'clr',
            ],
        ],
        'size' => [
            'label' => ['Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'],
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => [
                'rgxp' => 'natural',
                'includeBlankOption' => true,
                'nospace' => true,
                'helpwizard' => true,
                'tl_class' => 'w50',
            ],
        ],
        'text' => [
            'label' => ['Text', 'Geben Sie den Beschreibungstext ein'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
        ],
        'buttons' => [
            'label' => ['Buttons', ''],
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 20,
            'eval' => ['tl_class' => 'clr'],
            'fields' => ButtonHelper::getButtonConfig(),
        ],
    ],
];

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);