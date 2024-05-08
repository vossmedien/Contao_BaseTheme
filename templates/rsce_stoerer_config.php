<?php
// rsce_my_element_config.php
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

        'settings_position' => array(
            'label' => array('Positionierung', ''),
            'inputType' => 'group',
        ),


        'text_rotation' => array(
            'label' => array('Gradzahl der Drehung', 'Falls der Störer im Uhrzeigersinn geneigt werden soll'),
            'inputType' => 'text',
            'eval' => array('tl_class' => ''),
        ),

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
                    'options' => array(
                        /* Fading entrances  */
                        'animate__fadeInUp' => 'fadeInUp (Meistens Standard)',
                        'no-animation' => 'Keine Animation',
                        'animate__fadeIn' => 'fadeIn',
                        'animate__fadeInDown' => 'fadeInDown',
                        'animate__fadeInDownBig' => 'fadeInDownBig',
                        'animate__fadeInLeft' => 'fadeInLeft',
                        'animate__fadeInLeftBig' => 'fadeInLeftBig',
                        'animate__fadeInRight' => 'fadeInRight',
                        'animate__fadeInRightBig' => 'fadeInRightBig',
                        'animate__fadeInUpBig' => 'fadeInUpBig',
                        'animate__fadeInTopLeft' => 'fadeInTopLeft',
                        'animate__fadeInTopRight' => 'fadeInTopRight',
                        'animate__fadeInBottomLeft' => 'fadeInBottomLeft',
                        'animate__fadeInBottomRight' => 'fadeInBottomRight',
                        /* Attention seekers  */
                        'animate__bounce' => 'bounce',
                        'animate__flash' => 'flash',
                        'animate__pulse' => 'pulse',
                        'animate__rubberBand' => 'rubberBand',
                        'animate__shakeX' => 'shakeX',
                        'animate__shakeY' => 'shakeY',
                        'animate__headShake' => 'headShake',
                        'animate__swing' => 'swing',
                        'animate__tada' => 'tada',
                        'animate__wobble' => 'wobble',
                        'animate__jello' => 'jello',
                        'animate__heartBeat' => 'heartBeat',
                        /* Back entrances */
                        'animate__backInDown' => 'backInDown',
                        'animate__backInLeft' => 'backInLeft',
                        'animate__backInRight' => 'backInRight',
                        'animate__backInUp' => 'backInUp',
                        /* Bouncing entrances  */
                        'animate__bounceIn' => 'bounceIn',
                        'animate__bounceInDown' => 'bounceInDown',
                        'animate__bounceInLeft' => 'bounceInLeft',
                        'animate__bounceInRight' => 'bounceInRight',
                        'animate__bounceInUp' => 'bounceInUp',
                        /* Flippers */
                        'animate__flip' => 'flip',
                        'animate__flipInX' => 'flipInX',
                        'animate__flipInY' => 'flipInY',
                        /* Lightspeed */
                        'animate__lightSpeedInRight' => 'lightSpeedInRight',
                        'animate__lightSpeedInLeft' => 'lightSpeedInLeft',
                        /* Rotating entrances */
                        'animate__rotateIn' => 'rotateIn',
                        'animate__rotateInDownLeft' => 'rotateInDownLeft',
                        'animate__rotateInDownRight' => 'rotateInDownRight',
                        'animate__rotateInUpLeft' => 'rotateInUpLeft',
                        'animate__rotateInUpRight' => 'rotateInUpRight',
                        /* Specials */
                        'animate__hinge' => 'hinge',
                        'animate__jackInTheBox' => 'jackInTheBox',
                        'animate__rollIn' => 'rollIn',
                        /* Zooming entrances */
                        'animate__zoomIn' => 'zoomIn',
                        'animate__zoomInDown' => 'zoomInDown',
                        'animate__zoomInLeft' => 'zoomInLeft',
                        'animate__zoomInRight' => 'zoomInRight',
                        'animate__zoomInUp' => 'zoomInUp',
                        /* Sliding entrances */
                        'animate__slideInDown' => 'slideInDown',
                        'animate__slideInLeft' => 'slideInLeft',
                        'animate__slideInRight' => 'slideInRight',
                        'animate__slideInUp' => 'slideInUp',
                    ),
                    'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
                ),

                'settings_color' => array(
                    'label' => array('Farben', ''),
                    'inputType' => 'group',
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
                    'label' => array('Farben', ''),
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
