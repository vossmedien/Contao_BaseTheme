<?php


use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

//rsce_my_element_config.php
$config = array(
    'label' => array('Custom | Bild (image)', ''),
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
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true')
        ),
        'image' => array(
            'label' => array('Bild', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg,tif,webp',
            ),
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
        // Neue Felder
        'alt' => array(
            'label' => array('Alternativer Text', 'Beschreibung des Bildes für Screenreader und wenn das Bild nicht geladen werden kann'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'imageTitle' => array(
            'label' => array('Bildtitel', 'Titel des Bildes (wird als Tooltip angezeigt)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'imageUrl' => array(
            'label' => array('Bildlink-Adresse (URL)', 'Geben Sie eine URL ein, zu der das Bild verlinkt werden soll'),
            'inputType' => 'url',
            'eval' => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50 wizard'),
        ),
        'caption' => array(
            'label' => array('Bildunterschrift', 'Kurze Beschreibung unterhalb des Bildes'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'fullsize' => array(
            'label' => array('Großansicht/Neues Fenster', 'Bild-Großansicht und Link in neuem Fenster öffnen'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 m12'),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);