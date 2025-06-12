<?php


use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

$config = [
    'label' => ['Custom | Kopfbereich (headblock)', ''],
    'types' => ['content'],
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => ['cssID', 'abTestVariant'],
    'wrapper' => [
        'type' => 'none',
    ],
    'fields' => [
        'topline' => [
            'label' => ['Topline', 'Text oberhalb der Überschrift'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'allowHtml' => true],
        ],
        'subline' => [
            'label' => ['Subline', 'Text unterhalb der Überschrift'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'allowHtml' => true],
        ],
        'animation_type' => [
            'label' => ['Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'],
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => ['chosen' => 'true', 'tl_class' => 'clr']
        ],
        'layout' => [
            'label' => ['Layout', ''],
            'inputType' => 'select',
            'options' => [
                'two_column' => 'Zweispaltig',
                'image_top' => 'Bild oben / Text unten'
            ],
            'eval' => ['tl_class' => 'w50']
        ],
        'image' => [
            'label' => ['Bild', ''],
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr']
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
            'label' => ['Text', ''],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
        ],


        'size_background' => [
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
        'text_background_image' => [
            'label' => ['Hintergrundbild für Textbereich', ''],
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr']
        ],
        'text_color' => [
            'label' => ['Schriftfarbe', ''],
            'inputType' => 'text',
            'eval' => [ 'tl_class' => 'w50']
        ],
        'pull_up_next_element' => [
            'label' => ['Nächstes Element hochziehen', ''],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50']
        ],

        'buttons' => [
            'label' => ['Buttons', ''],
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 3,
            'fields' => ButtonHelper::getButtonConfig(),
        ],
    ],
];

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);