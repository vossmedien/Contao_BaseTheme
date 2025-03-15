<?php


use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

//rsce_my_element_config.php
return array(
    'label' => array('Custom | Runde Boxen mit Icon / Bild sowie Verlinkung (bubblelist)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
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
        'backgroundcolor' => array(
            'label' => array('Hintergrundfarbe', 'Im Hexformat, z. B. #000 für schwarz (Standard: weiß)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'linkcolor' => array(
            'label' => array('Schriftfarbe', 'Im Hexformat, z. B. #000 für schwarz (Standard: Hauptfarbe)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
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
                'img' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                        'tl_class' => 'w50'
                    ),
                ),




                'color' => array(
                    'label' => array('Alternative Farbe für Element', 'Standard: Hauptfarbe'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'hover_color' => array(
                    'label' => array('Alternative Hover-Farbe für Element', 'Standard: Sekundärfarbe'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'icon' => array(
                    'label' => array('Alternativ zum Bild Font-Awesome Klasse angeben', 'überschreibt das Bild, z. B. fa-facebook fab'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'clr'),
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
