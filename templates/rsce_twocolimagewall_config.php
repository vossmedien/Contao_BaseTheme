<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Zeile mit zwei Spalten & vollflächigen Bildern', ''),
    'types' => array('content'),
    'contentCategory' => 'texts',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
        ),

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

                /* Back exits */
                'animate__backOutDown' => 'backOutDown',
                'animate__backOutLeft' => 'backOutLeft',
                'animate__backOutRight' => 'backOutRight',
                'animate__backOutUp' => 'backOutUp',

                /* Bouncing entrances  */
                'animate__bounceIn' => 'bounceIn',
                'animate__bounceInDown' => 'bounceInDown',
                'animate__bounceInLeft' => 'bounceInLeft',
                'animate__bounceInRight' => 'bounceInRight',
                'animate__bounceInUp' => 'bounceInUp',

                /* Bouncing exits  */
                'animate__bounceOut' => 'bounceOut',
                'animate__bounceOutDown' => 'bounceOutDown',
                'animate__bounceOutLeft' => 'bounceOutLeft',
                'animate__bounceOutRight' => 'bounceOutRight',
                'animate__bounceOutUp' => 'bounceOutUp',

                /* Fading exits */
                'animate__fadeOut' => 'fadeOut',
                'animate__fadeOutDown' => 'fadeOutDown',
                'animate__fadeOutDownBig' => 'fadeOutDownBig',
                'animate__fadeOutLeft' => 'fadeOutLeft',
                'animate__fadeOutLeftBig' => 'fadeOutLeftBig',
                'animate__fadeOutRight' => 'fadeOutRight',
                'animate__fadeOutRightBig' => 'fadeOutRightBig',
                'animate__fadeOutUp' => 'fadeOutUp',
                'animate__fadeOutUpBig' => 'fadeOutUpBig',
                'animate__fadeOutTopLeft' => 'fadeOutTopLeft',
                'animate__fadeOutTopRight' => 'fadeOutTopRight',
                'animate__fadeOutBottomRight' => 'fadeOutBottomRight',
                'animate__fadeOutBottomLeft' => 'fadeOutBottomLeft',

                /* Flippers */
                'animate__flip' => 'flip',
                'animate__flipInX' => 'flipInX',
                'animate__flipInY' => 'flipInY',
                'animate__flipOutX' => 'flipOutX',
                'animate__flipOutY' => 'flipOutY',

                /* Lightspeed */
                'animate__lightSpeedInRight' => 'lightSpeedInRight',
                'animate__lightSpeedInLeft' => 'lightSpeedInLeft',
                'animate__lightSpeedOutRight' => 'lightSpeedOutRight',
                'animate__lightSpeedOutLeft' => 'lightSpeedOutLeft',

                /* Rotating entrances */
                'animate__rotateIn' => 'rotateIn',
                'animate__rotateInDownLeft' => 'rotateInDownLeft',
                'animate__rotateInDownRight' => 'rotateInDownRight',
                'animate__rotateInUpLeft' => 'rotateInUpLeft',
                'animate__rotateInUpRight' => 'rotateInUpRight',

                /* Rotating exits */
                'animate__rotateOut' => 'rotateOut',
                'animate__rotateOutDownLeft' => 'rotateOutDownLeft',
                'animate__rotateOutDownRight' => 'rotateOutDownRight',
                'animate__rotateOutUpLeft' => 'rotateOutUpLeft',
                'animate__rotateOutUpRight' => 'rotateOutUpRight',

                /* Specials */
                'animate__hinge' => 'hinge',
                'animate__jackInTheBox' => 'jackInTheBox',
                'animate__rollIn' => 'rollIn',
                'animate__rollOut' => 'rollOut',

                /* Zooming entrances */
                'animate__zoomIn' => 'zoomIn',
                'animate__zoomInDown' => 'zoomInDown',
                'animate__zoomInLeft' => 'zoomInLeft',
                'animate__zoomInRight' => 'zoomInRight',
                'animate__zoomInUp' => 'zoomInUp',

                /* Zooming exits */
                'animate__zoomOut' => 'zoomOut',
                'animate__zoomOutDown' => 'zoomOutDown',
                'animate__zoomOutLeft' => 'zoomOutLeft',
                'animate__zoomOutRight' => 'zoomOutRight',
                'animate__zoomOutUp' => 'zoomOutUp',

                /* Sliding entrances */
                'animate__slideInDown' => 'slideInDown',
                'animate__slideInLeft' => 'slideInLeft',
                'animate__slideInRight' => 'slideInRight',
                'animate__slideInUp' => 'slideInUp',

                /* Sliding exits */
                'animate__slideOutDown' => 'slideOutDown',
                'animate__slideOutLeft' => 'slideOutLeft',
                'animate__slideOutRight' => 'slideOutRight',
                'animate__slideOutUp' => 'slideOutUp',
            ),
            'eval' => array('chosen' => 'true')
        ),

        'row' => array(
            'label' => array('Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 10,
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

                        /* Back exits */
                        'animate__backOutDown' => 'backOutDown',
                        'animate__backOutLeft' => 'backOutLeft',
                        'animate__backOutRight' => 'backOutRight',
                        'animate__backOutUp' => 'backOutUp',

                        /* Bouncing entrances  */
                        'animate__bounceIn' => 'bounceIn',
                        'animate__bounceInDown' => 'bounceInDown',
                        'animate__bounceInLeft' => 'bounceInLeft',
                        'animate__bounceInRight' => 'bounceInRight',
                        'animate__bounceInUp' => 'bounceInUp',

                        /* Bouncing exits  */
                        'animate__bounceOut' => 'bounceOut',
                        'animate__bounceOutDown' => 'bounceOutDown',
                        'animate__bounceOutLeft' => 'bounceOutLeft',
                        'animate__bounceOutRight' => 'bounceOutRight',
                        'animate__bounceOutUp' => 'bounceOutUp',

                        /* Fading exits */
                        'animate__fadeOut' => 'fadeOut',
                        'animate__fadeOutDown' => 'fadeOutDown',
                        'animate__fadeOutDownBig' => 'fadeOutDownBig',
                        'animate__fadeOutLeft' => 'fadeOutLeft',
                        'animate__fadeOutLeftBig' => 'fadeOutLeftBig',
                        'animate__fadeOutRight' => 'fadeOutRight',
                        'animate__fadeOutRightBig' => 'fadeOutRightBig',
                        'animate__fadeOutUp' => 'fadeOutUp',
                        'animate__fadeOutUpBig' => 'fadeOutUpBig',
                        'animate__fadeOutTopLeft' => 'fadeOutTopLeft',
                        'animate__fadeOutTopRight' => 'fadeOutTopRight',
                        'animate__fadeOutBottomRight' => 'fadeOutBottomRight',
                        'animate__fadeOutBottomLeft' => 'fadeOutBottomLeft',

                        /* Flippers */
                        'animate__flip' => 'flip',
                        'animate__flipInX' => 'flipInX',
                        'animate__flipInY' => 'flipInY',
                        'animate__flipOutX' => 'flipOutX',
                        'animate__flipOutY' => 'flipOutY',

                        /* Lightspeed */
                        'animate__lightSpeedInRight' => 'lightSpeedInRight',
                        'animate__lightSpeedInLeft' => 'lightSpeedInLeft',
                        'animate__lightSpeedOutRight' => 'lightSpeedOutRight',
                        'animate__lightSpeedOutLeft' => 'lightSpeedOutLeft',

                        /* Rotating entrances */
                        'animate__rotateIn' => 'rotateIn',
                        'animate__rotateInDownLeft' => 'rotateInDownLeft',
                        'animate__rotateInDownRight' => 'rotateInDownRight',
                        'animate__rotateInUpLeft' => 'rotateInUpLeft',
                        'animate__rotateInUpRight' => 'rotateInUpRight',

                        /* Rotating exits */
                        'animate__rotateOut' => 'rotateOut',
                        'animate__rotateOutDownLeft' => 'rotateOutDownLeft',
                        'animate__rotateOutDownRight' => 'rotateOutDownRight',
                        'animate__rotateOutUpLeft' => 'rotateOutUpLeft',
                        'animate__rotateOutUpRight' => 'rotateOutUpRight',

                        /* Specials */
                        'animate__hinge' => 'hinge',
                        'animate__jackInTheBox' => 'jackInTheBox',
                        'animate__rollIn' => 'rollIn',
                        'animate__rollOut' => 'rollOut',

                        /* Zooming entrances */
                        'animate__zoomIn' => 'zoomIn',
                        'animate__zoomInDown' => 'zoomInDown',
                        'animate__zoomInLeft' => 'zoomInLeft',
                        'animate__zoomInRight' => 'zoomInRight',
                        'animate__zoomInUp' => 'zoomInUp',

                        /* Zooming exits */
                        'animate__zoomOut' => 'zoomOut',
                        'animate__zoomOutDown' => 'zoomOutDown',
                        'animate__zoomOutLeft' => 'zoomOutLeft',
                        'animate__zoomOutRight' => 'zoomOutRight',
                        'animate__zoomOutUp' => 'zoomOutUp',

                        /* Sliding entrances */
                        'animate__slideInDown' => 'slideInDown',
                        'animate__slideInLeft' => 'slideInLeft',
                        'animate__slideInRight' => 'slideInRight',
                        'animate__slideInUp' => 'slideInUp',

                        /* Sliding exits */
                        'animate__slideOutDown' => 'slideOutDown',
                        'animate__slideOutLeft' => 'slideOutLeft',
                        'animate__slideOutRight' => 'slideOutRight',
                        'animate__slideOutUp' => 'slideOutUp',
                    ),
                    'eval' => array('chosen' => 'true')
                ),

                'column_width' => array(
                    'label' => array(
                        'de' => array('Inhalts-Spaltenbreite', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12 col-md-6 col-lg-3' => '25%',
                        'col-12 col-md-6 col-lg-4' => '33%',
                        'col-12 col-md-6' => '50%',
                        'col-12 col-lg-8' => '66.66%',
                        'col-12 col-lg-9' => '75%',
                        'col-12' => 'Volle Breite',
                    ),
                ),


                'reverse' => array(
                    'label' => array('Spalten umkehren', 'Funktioniert NUR mit 50% Spalten!'),
                    'inputType' => 'checkbox',
                ),


                'image' => array(
                    'label' => array('Boxen-Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),

                'image_both' => array(
                    'label' => array('Bild als Hintergrund für beide Spalten', 'Sonst ist jedes zweites Element automatisch auf der anderen Seite'),
                    'inputType' => 'checkbox',
                ),


                'alternate_image' => array(
                    'label' => array('Code als alternative zum Bild in Spalte anzeigen', 'z. B. Googlemap-Frame'),
                    'inputType' => 'textarea',
                ),


                'alternate_background' => array(
                    'label' => array('Alternative Hintergrundfarbe Inhaltsspalte', 'In HEX angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'alternate_textcolor' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'textalign' => array(
                    'label' => array(
                        'de' => array('Text-Ausrichtung', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'text-start' => 'Linksbündig',
                        'text-center' => 'Zentriert',
                        'text-end' => 'Rechtsbündig',
                    ),
                    'eval' => array('tl_class' => 'clr'),
                ),


                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', 'Ausrichtung der Elemente'),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'h1' => 'H1',
                        'h2' => 'H2',
                        'h3' => 'H3',
                        'h4' => 'H4',
                        'h5' => 'H5',
                    ),
                ),

                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                ),


                'ce_headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
                ),

                'ce_subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'darken_content' => array(
                    'label' => array('Inhaltsspalte abdunkeln', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),

                'content' => array(
                    'label' => array('Text (linke Spalte)', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),


                'content_rightcol' => array(
                    'label' => array('Extra Textfeld für rechte Spalte', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),

                'buttons' => array(
                    'label' => array('Buttons für Inhaltsspalte (links)', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 20,
                    'fields' => array(
                        'animation_type' => array(
                            'label' => array(
                                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                            ),
                            'inputType' => 'select',
                            'options' => array(
                                'no-animation' => 'Keine Animation',

                                /* Fading entrances  */
                                'animate__fadeInUp' => 'fadeInUp (Meistens Standard)',
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

                                /* Back exits */
                                'animate__backOutDown' => 'backOutDown',
                                'animate__backOutLeft' => 'backOutLeft',
                                'animate__backOutRight' => 'backOutRight',
                                'animate__backOutUp' => 'backOutUp',

                                /* Bouncing entrances  */
                                'animate__bounceIn' => 'bounceIn',
                                'animate__bounceInDown' => 'bounceInDown',
                                'animate__bounceInLeft' => 'bounceInLeft',
                                'animate__bounceInRight' => 'bounceInRight',
                                'animate__bounceInUp' => 'bounceInUp',

                                /* Bouncing exits  */
                                'animate__bounceOut' => 'bounceOut',
                                'animate__bounceOutDown' => 'bounceOutDown',
                                'animate__bounceOutLeft' => 'bounceOutLeft',
                                'animate__bounceOutRight' => 'bounceOutRight',
                                'animate__bounceOutUp' => 'bounceOutUp',

                                /* Fading exits */
                                'animate__fadeOut' => 'fadeOut',
                                'animate__fadeOutDown' => 'fadeOutDown',
                                'animate__fadeOutDownBig' => 'fadeOutDownBig',
                                'animate__fadeOutLeft' => 'fadeOutLeft',
                                'animate__fadeOutLeftBig' => 'fadeOutLeftBig',
                                'animate__fadeOutRight' => 'fadeOutRight',
                                'animate__fadeOutRightBig' => 'fadeOutRightBig',
                                'animate__fadeOutUp' => 'fadeOutUp',
                                'animate__fadeOutUpBig' => 'fadeOutUpBig',
                                'animate__fadeOutTopLeft' => 'fadeOutTopLeft',
                                'animate__fadeOutTopRight' => 'fadeOutTopRight',
                                'animate__fadeOutBottomRight' => 'fadeOutBottomRight',
                                'animate__fadeOutBottomLeft' => 'fadeOutBottomLeft',

                                /* Flippers */
                                'animate__flip' => 'flip',
                                'animate__flipInX' => 'flipInX',
                                'animate__flipInY' => 'flipInY',
                                'animate__flipOutX' => 'flipOutX',
                                'animate__flipOutY' => 'flipOutY',

                                /* Lightspeed */
                                'animate__lightSpeedInRight' => 'lightSpeedInRight',
                                'animate__lightSpeedInLeft' => 'lightSpeedInLeft',
                                'animate__lightSpeedOutRight' => 'lightSpeedOutRight',
                                'animate__lightSpeedOutLeft' => 'lightSpeedOutLeft',

                                /* Rotating entrances */
                                'animate__rotateIn' => 'rotateIn',
                                'animate__rotateInDownLeft' => 'rotateInDownLeft',
                                'animate__rotateInDownRight' => 'rotateInDownRight',
                                'animate__rotateInUpLeft' => 'rotateInUpLeft',
                                'animate__rotateInUpRight' => 'rotateInUpRight',

                                /* Rotating exits */
                                'animate__rotateOut' => 'rotateOut',
                                'animate__rotateOutDownLeft' => 'rotateOutDownLeft',
                                'animate__rotateOutDownRight' => 'rotateOutDownRight',
                                'animate__rotateOutUpLeft' => 'rotateOutUpLeft',
                                'animate__rotateOutUpRight' => 'rotateOutUpRight',

                                /* Specials */
                                'animate__hinge' => 'hinge',
                                'animate__jackInTheBox' => 'jackInTheBox',
                                'animate__rollIn' => 'rollIn',
                                'animate__rollOut' => 'rollOut',

                                /* Zooming entrances */
                                'animate__zoomIn' => 'zoomIn',
                                'animate__zoomInDown' => 'zoomInDown',
                                'animate__zoomInLeft' => 'zoomInLeft',
                                'animate__zoomInRight' => 'zoomInRight',
                                'animate__zoomInUp' => 'zoomInUp',

                                /* Zooming exits */
                                'animate__zoomOut' => 'zoomOut',
                                'animate__zoomOutDown' => 'zoomOutDown',
                                'animate__zoomOutLeft' => 'zoomOutLeft',
                                'animate__zoomOutRight' => 'zoomOutRight',
                                'animate__zoomOutUp' => 'zoomOutUp',

                                /* Sliding entrances */
                                'animate__slideInDown' => 'slideInDown',
                                'animate__slideInLeft' => 'slideInLeft',
                                'animate__slideInRight' => 'slideInRight',
                                'animate__slideInUp' => 'slideInUp',

                                /* Sliding exits */
                                'animate__slideOutDown' => 'slideOutDown',
                                'animate__slideOutLeft' => 'slideOutLeft',
                                'animate__slideOutRight' => 'slideOutRight',
                                'animate__slideOutUp' => 'slideOutUp',
                            ),
                            'eval' => array('chosen' => 'true')
                        ),

                        'link_text' => array(
                            'label' => array('Link-Beschriftung', ''),
                            'inputType' => 'text',
                        ),
                        'link_url' => array(
                            'label' => array('Verlinkung der Beschriftung', ''),
                            'inputType' => 'url',
                        ),

                        'link_betreff' => array(
                            'label' => array('Betreffzeile für "mailto:"-Buttons', '(optional, falls Link eine neue Email öffnen soll)'),
                            'inputType' => 'text',
                        ),

                        'link_type' => array(
                            'label' => array(
                                'de' => array('Farbe des Buttons', ''),
                            ),
                            'inputType' => 'select',
                            'options' => array(
                                'btn-primary' => 'Hauptfarbe',
                                'btn-outline-primary' => 'Hauptfarbe (Outline)',
                                'btn-secondary' => 'Sekundär-Farbe',
                                'btn-outline-secondary' => 'Sekundär-Farbe (Outline)',
                            ),
                        ),
                        'link_size' => array(
                            'label' => array(
                                'de' => array('Größe des Buttons', ''),
                            ),
                            'inputType' => 'select',
                            'options' => array(
                                '' => 'Standard',
                                'btn-sm' => 'Klein',
                                'btn-lg' => 'Groß',
                            ),
                        ),
                    ),
                ),


            ),
        ),

    ),
);
