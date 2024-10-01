<?php

use VSM_HelperFunctions\ButtonHelper;
use VSM_HelperFunctions\GlobalElementConfig;

//rsce_my_element_config.php
return array(
    'label' => array('Custom | Fixed Störer (stoerer)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(


        'is_fixed' => array(
            'label' => array('Störer scrollt mit', ''),
            'inputType' => 'checkbox',
        ),

        'size' => array(
            'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
            ),
        ),

        'size_mobile' => array(
            'label' => array('Bildgröße (mobile)', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
            ),
        ),

        'settings_position' => array(
            'label' => array('Positionierung', ''),
            'inputType' => 'group',
        ),


        /*
               'text_rotation' => array(
            'label' => array('Gradzahl der Drehung', 'Falls der Störer im Uhrzeigersinn geneigt werden soll'),
            'inputType' => 'text',
            'eval' => array('tl_class' => ''),
        ),
         */


        'alternate_top_position' => array(
            'label' => array('Abstand von oberer Bildschirmkante', 'Standard: 150px - entweder OBEN oder UNTEN ausfüllen'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'alternate_bottom_position' => array(
            'label' => array('Abstand von unterer Bildschirmkante', 'entweder OBEN oder UNTEN ausfüllen'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'alternate_right_position' => array(
            'label' => array('Abstand von rechter Bildschirmkante', 'Standard: 25px - entweder RECHTS oder LINKS ausfüllen'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'alternate_left_position' => array(
            'label' => array('Abstand von linker Bildschirmkante', 'entweder RECHTS oder LINKS ausfüllen'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'expand' => array(
            'label' => array('Inhalt nach Hover ausklappen', 'Standardmäßig immer sichtbar'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'stoerer' => array(
            'label' => array('Elemente', ''),
            'elementLabel' => '%s. Element',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(
                'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
                ),

                'settings_color' => array(
                    'label' => array('Farben', ''),
                    'inputType' => 'group',
                    'eval' => array('collapsible' => true, 'collapsed' => true),
                ),

                'text_color' => array(
                    'label' => array('Schriftfarbe als HEX-Wert', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'text_hover_color' => array(
                    'label' => array('Schriftfarbe (hover) als HEX-Wert', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'bg_color' => array(
                    'label' => array('Hintergrundfarbe als HEX-Wert', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'bg_hover_color' => array(
                    'label' => array('Hintergrundfarbe (hover) als HEX-Wert', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'settings_inhalt' => array(
                    'label' => array('Inhalt', ''),
                    'inputType' => 'group',
                ),


                'img' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    ),
                ),

                'img_mobile' => array(
                    'label' => array('Bild (mobile)', 'Alternativbild für mobile Geräte'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    ),
                ),
                'content' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),

                'link_url' => array(
                    'label' => array('Verlinkung', ''),
                    'inputType' => 'url',
                ),

                'new_tab' => array(
                    'label' => array('Link in neuen Tab öffnen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),
            ),
        ),
    ),
);
