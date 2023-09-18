<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Bild + Text&Button', ''),
    'types' => array('content'),
    'contentCategory' => 'texts',
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
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),

        'content_text' => array(
            'label' => array('Langtext', ''),
            'inputType' => 'text',
            'eval' => array('rte' => 'tinyMCE'),
        ),

        'row_align' => array(
            'label' => array(
                'de' => array('Ausrichtung der Spalten', 'wird ignoriert bei gleich hohen Boxen'),
            ),
            'inputType' => 'select',
            'options' => array(
                '' => 'Oben (Standard)',
                'align-items-center' => 'Mittig',
                'align-items-end' => 'Unten',
            ),
        ),

        'as_rows' => array(
            'label' => array('Als Zeilen darstellen', 'Ansonsten wird der Inhalt über drei Spalten angezeigt'),
            'inputType' => 'checkbox',
        ),

        'rows' => array(
            'label' => array('Zeilen', ''),
            'elementLabel' => '%s. Zeile',
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

                'reverse_columns' => array(
                    'label' => array('Spaltenreihenfolge umkehren', 'Nur bei Zeilendarstellung! Button erscheint unter dem Bild'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),


                'settings_1' => array(
                    'label' => array('Inhalt', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),
                    'dependsOn' => array(
                        'field' => 'selecttype',
                        'value' => 'multiple',
                    ),
                ),


                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', 'für linke Spalte'),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'h1' => 'H1 (Haupt-Headline für SEO, darf nur 1x vorkommen)',
                        'h2' => 'H2 (Sollte H1 thematisch untergeordnet sein)',
                        'h3' => 'H3 (Sollte H2 thematisch untergeordnet sein)',
                        'h4' => 'H4',
                        'h5' => 'H5',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),
                'headline' => array(
                    'label' => array('Überschrift', 'für linke Spalte'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),

                'text' => array(
                    'label' => array('Langtext', ''),
                    'inputType' => 'text',
                    'eval' => array('rte' => 'tinyMCE'),
                ),

                'column_width' => array(
                    'label' => array(
                        'de' => array('Breite für Bildspalte', 'Breite der Bildspalte (falls vorhanden), Standard: 50%'),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12 col-md-auto' => 'Breite anhand des Inhalts & mobile untereinander',
                        'col-12 col-lg-4 col-xl-3' => '25%',
                        'col-12 col-lg-4' => '33%',
                        'col-12 col-lg-6' => '50%',
                        'col-12 col-lg-8' => '66.66%',
                        'col-12 col-lg-9' => '75%',
                        'col-12' => 'Volle Breite',
                        'col-12 col-lg' => 'Automatische Breite (füllend)',

                    ),

                ),


                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 20,


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

                        'new_tab' => array(
                            'label' => array('Link in neuen Tab öffnen', ''),
                            'inputType' => 'checkbox',
                            'eval' => array('tl_class' => 'clr'),
                        ),
                        'link_text' => array(
                            'label' => array('Link-Beschriftung', ''),
                            'inputType' => 'text',
                            'eval' => array('allowHtml' => true),
                        ),
                        'link_betreff' => array(
                            'label' => array('Betreffzeile für "mailto:"-Buttons', '(optional, falls Link eine neue Email öffnen soll)'),
                            'inputType' => 'text',
                            'eval' => array('tl_class' => 'w50'),
                        ),
                        'link_url' => array(
                            'label' => array('Verlinkung', ''),
                            'inputType' => 'url',
                            'eval' => array('tl_class' => 'w50'),
                        ),

                        'link_type' => array(
                            'label' => array(
                                'de' => array('Optik des Buttons', ''),
                            ),
                            'inputType' => 'select',
                            'options' => array(
                                'btn-primary' => 'Hauptfarbe',
                                'btn-outline-primary' => 'Hauptfarbe(Outline)',
                                'btn-secondary' => 'Sekundär - Farbe',
                                'btn-outline-secondary' => 'Sekundär - Farbe(Outline)',
                                'btn-link with-arrow' => 'Link - Optik mit Pfeilen',
                                'btn-outline-black' => 'Transparenter Button mit schwarzer Schrift und Rahmen', 'btn-outline-white' => 'Transparenter Button mit weißer Schrift und Rahmen',
                                'btn-white' => 'Weißer Button mit schwarzer Schrift',
                            ),
                            'eval' => array('tl_class' => 'w50'),
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
                            'eval' => array('tl_class' => 'w50'),
                        ),


                    ),
                ),


                'settings_3' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),
                    'dependsOn' => array(
                        'field' => 'selecttype',
                        'value' => 'multiple',
                    ),
                ),


                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),


                'size' => array(
                    'label' => array('Bildbreite und Bildhöhe', ''),
                    'inputType' => 'imageSize',
                    'options' => System::getImageSizes(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'tl_class' => 'w50',
                        'includeBlankOption' => true,
                    ),
                ),

            ),
        ),
    ),
);
