<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Zeile mit zwei Spalten & vollflächigen Bildern (twocolimagewall)', ''),
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

        'no_padding' => array(
            'label' => array('Abstand komplett entfernen', 'Das vorherige und nächste Element bzw. der Footer "hängen" dann direkt an diesem Element'),
            'inputType' => 'checkbox',
        ),

        'not_fullwidth' => array(
            'label' => array('Element nicht auf die komplette Breite ziehen', ''),
            'inputType' => 'checkbox',
        ),


        'row' => array(
            'label' => array('Zeilen', ''),
            'elementLabel' => '%s. Zeile',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 10,
            'fields' => array(
                'row_name' => array(
                    'label' => array('Zeilen-Bezeichnung', 'dient rein zur Orientierung, hat keinen Einfluss auf Frontend'),
                    'inputType' => 'text',
                ),

                'settings_1' => array(
                    'label' => array('Einstellungen', ''),
                    'inputType' => 'group',
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
                'reverse' => array(
                    'label' => array('Spalten umkehren', 'die Positionen der linken und rechten Spalte werden getauscht'),
                    'inputType' => 'checkbox',
                ),
                'bottom_spacing' => array(
                    'label' => array('Abstand zur nächsten Zeile', 'Ansonsten liegen beide Zeilen direkt aneinander'),
                    'inputType' => 'checkbox',
                ),
                'spacing_between' => array(
                    'label' => array('Zwischenabstand zwischen den Spalten', 'Ansonsten liegen beide Spalten direkt aneinander'),
                    'inputType' => 'checkbox',
                ),

                'min-height' => array(
                    'label' => array('Mindesthöhe', ''),
                    'inputType' => 'checkbox',
                ),


                'boxedHeadline' => array(
                    'label' => array('"Boxed-Headline"', ''),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Verwende eine "Boxed-Headline", die mittig über beiden Spalten liegt.',
                    ),
                ),

                'kachel_left' => array(
                    'label' => array('Kachel: linke Spalte ', ''),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Kachel für linke Spalte hinzufügen',
                    ),
                ),
                'kachel_right' => array(
                    'label' => array('Kachel: rechte Spalte ', ''),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Kachel für rechte Spalte hinzufügen',
                    ),
                ),

                'settings_2' => array(
                    'label' => array('Boxed-Headline', ''),
                    'inputType' => 'group',
                    'dependsOn' => array(
                        'field' => 'boxedHeadline',
                        'value' => '1',
                    ),
                ),
                'boxed_headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'h1' => 'H1',
                        'h2' => 'H2',
                        'h3' => 'H3',
                        'h4' => 'H4',
                        'h5' => 'H5',
                    ),
                    'eval' => array('tl_class' => 'clr '),
                ),
                'boxed_headline_onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'boxed_headline' => array(
                    'label' => array('Überschrift auf Spaltenmitte liegend', 'In weißer Box mit schatten'),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
                ),
                'boxed_subheadline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
                ),


                'settings_5' => array(
                    'label' => array('Kachel linke Spalte', ''),
                    'inputType' => 'group',
                    'dependsOn' => array(
                        'field' => 'kachel_left',
                        'value' => '1',
                    ),
                ),
                'animation_type_left_kachel' => array(
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

                'kachel_left_position' => array(
                    'label' => array(
                        'de' => array('Kachel-Position', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'pos-centered' => 'Mittig',
                        'pos-centered-right' => 'Mittig rechts',
                        'pos-centered-left' => 'Mittig links',
                        'pos-bottom-right' => 'Unten rechts',
                        'pos-bottom-left' => 'Unten links',
                        'pos-bottom-center' => 'Unten mittig',
                        'pos-top-right' => 'Oben rechts',
                        'pos-top-left' => 'Oben links',
                        'pos-top-center' => 'Oben mittig'
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),


                'kachel_left_text_position' => array(
                    'label' => array(
                        'de' => array('Textausrichtung innerhalb der Kachel', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'justify-content-center align-items-center' => 'Mittig',
                        'justify-content-center align-items-end' => 'Mittig rechts',
                        'justify-content-center' => 'Mittig links',
                        'align-items-end' => 'Oben rechts',
                        'text-center' => 'Oben zentriert',
                        'justify-content-end align-items-end' => 'Unten rechts',
                        'justify-content-end' => 'Unten links',
                        'justify-content-end text-center' => 'Unten zentriert',
                        '' => 'Oben links'
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),

                'kachel_left_background_color' => array(
                    'label' => array('Hintergrundfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'kachel_left_text_color' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'kachel_left_text' => array(
                    'label' => array('Headline für Kachel in linker Spalte', 'HTML ist erlaubt'),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'clr', 'tl_class' => 'w50'),
                ),


                'kachel_left_url' => array(
                    'label' => array('Verlinkung', ''),
                    'inputType' => 'url',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'expand_left_kachel' => array(
                    'label' => array('Kachel bei Hover vergrößern und Text anzeigen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                    'options' => array(
                        '1' => 'Kachel bei Hover vergrößern und Text anzeigen',
                    ),
                ),


                'kachel_left_hover_text' => array(
                    'label' => array('Text für Kachel', 'HTML ist erlaubt'),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                    'dependsOn' => array(
                        'field' => 'expand_left_kachel',
                        'value' => '1',
                    ),
                ),


                'kachel_left_button_text' => array(
                    'label' => array('Button-Text', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'expand_left_kachel',
                        'value' => '1',
                    ),
                ),

                'settings_6' => array(
                    'label' => array('Kachel rechte Spalte', ''),
                    'inputType' => 'group',
                    'dependsOn' => array(
                        'field' => 'kachel_right',
                        'value' => '1',
                    ),
                ),
                'animation_type_right_kachel' => array(
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

                'kachel_right_position' => array(
                    'label' => array(
                        'de' => array('Kachel-Position', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'pos-centered' => 'Mittig',
                        'pos-centered-right' => 'Mittig rechts',
                        'pos-centered-left' => 'Mittig links',
                        'pos-bottom-right' => 'Unten rechts',
                        'pos-bottom-left' => 'Unten links',
                        'pos-bottom-center' => 'Unten mittig',
                        'pos-top-right' => 'Oben rechts',
                        'pos-top-left' => 'Oben links',
                        'pos-top-center' => 'Oben mittig'
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),

                'kachel_right_text_position' => array(
                    'label' => array(
                        'de' => array('Textausrichtung innerhalb der Kachel', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'justify-content-center align-items-center' => 'Mittig',
                        'justify-content-center align-items-end' => 'Mittig rechts',
                        'justify-content-center' => 'Mittig links',
                        'align-items-end' => 'Oben rechts',
                        'text-center' => 'Oben zentriert',
                        'justify-content-end align-items-end' => 'Unten rechts',
                        'justify-content-end' => 'Unten links',
                        'justify-content-end text-center' => 'Unten zentriert',
                        '' => 'Oben links'
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),


                'kachel_right_background_color' => array(
                    'label' => array('Hintergrundfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'kachel_right_text_color' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'kachel_right_text' => array(
                    'label' => array('Headline für Kachel in rechter Spalte', 'HTML ist erlaubt'),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'clr', 'tl_class' => 'w50'),
                ),

                'kachel_right_url' => array(
                    'label' => array('Verlinkung', ''),
                    'inputType' => 'url',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'expand_right_kachel' => array(
                    'label' => array('Kachel bei Hover vergrößern und Text anzeigen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                    'options' => array(
                        '1' => 'Kachel bei Hover vergrößern und Text anzeigen',
                    ),
                ),


                'kachel_right_hover_text' => array(
                    'label' => array('Text für Kachel', 'HTML ist erlaubt'),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                    'dependsOn' => array(
                        'field' => 'expand_right_kachel',
                        'value' => '1',
                    ),
                ),


                'kachel_right_button_text' => array(
                    'label' => array('Button-Text', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'expand_right_kachel',
                        'value' => '1',
                    ),
                ),


                'settings_3' => array(
                    'label' => array('Linke Spalte (Inhaltsspalte)', ''),
                    'inputType' => 'group',
                ),
                'darken_content' => array(
                    'label' => array('Linke Spalte abdunkeln', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'image_leftcol' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                        'tl_class' => 'clr'
                    ),
                ),

                'size_left' => array(
                    'label' => array('Bildbreite und Bildhöhe', ''),
                    'inputType' => 'imageSize',
                    'options' => System::getImageSizes(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                    ),
                ),

                'column_width' => array(
                    'label' => array(
                        'de' => array('Breite der linken Spalte', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12 col-lg-6 col-xxl-4' => '33%',
                        'col-12 col-lg-6 col-xxl-5' => 'ca. 40%',
                        'col-12 col-lg-6' => '50%',
                        'col-12 col-lg-6 col-xxl-8' => '66.66%',
                        'col-12 col-lg-6 col-xxl-9' => '75%',
                        'col-12 full-width' => '100% (keine Rechte Spalte!)'
                    ),
                ),

                'alternate_background' => array(
                    'label' => array('Alternative Hintergrundfarbe für linke Spalte', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'alternate_textcolor' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                /*
                'innerpadding' => array(
                    'label' => array('Innenabstand (oben/unten) innerhalb der Zeile', 'Funktioniert nur bei alternativer Hintergrundfarbe'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),
                */


                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'h1' => 'H1',
                        'h2' => 'H2',
                        'h3' => 'H3',
                        'h4' => 'H4',
                        'h5' => 'H5',
                    ),
                    'eval' => array('tl_class' => 'clr'),
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
                'content' => array(
                    'label' => array('Text (linke Spalte)', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),
                'textalign' => array(
                    'label' => array(
                        'de' => array('Text-Ausrichtung', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'text-md-start' => 'Linksbündig',
                        'text-md-center' => 'Zentriert',
                        'text-md-end' => 'Rechtsbündig',
                    ),
                    'eval' => array('tl_class' => 'clr'),
                ),


                'add_buttons' => array(
                    'label' => array('Buttons zu linker Spalte hinzufügen', ''),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Buttons zu linker Spalte hinzufügen',
                    ),
                ),

                'buttons' => array(
                    'label' => array('Buttons für linke Spalte', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 20,

                    'dependsOn' => array(
                        'field' => 'add_buttons',
                        'value' => '1',
                    ),

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
                                'btn-outline-primary' => 'Hauptfarbe (Outline)',
                                'btn-secondary' => 'Sekundär-Farbe',
                                'btn-outline-secondary' => 'Sekundär-Farbe (Outline)',
                                'btn-link with-arrow' => 'Link-Optik mit Pfeilen',
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

                        'new_tab' => array(
                            'label' => array('Link in neuen Tab öffnen', ''),
                            'inputType' => 'checkbox',
                            'eval' => array('tl_class' => 'clr'),
                        ),
                    ),
                ),

                'settings_4' => array(
                    'label' => array('Rechte Spalte (Bildspalte)', ''),
                    'inputType' => 'group',
                ),


                'contentType' => array(
                    'label' => array('Bild oder iFrame einbinden ', ''),
                    'inputType' => 'radio',
                    'options' => array(
                        '1' => 'Ein Bild einbinden',
                        '2' => 'Ein iFrame, z. B. eine Google-Map einbinden',
                        '3' => 'Ein Video (mp4) einbinden',
                    ),
                ),

                'not_as_bg' => array(
                    'label' => array('Bild nicht als "Hintergrund" einfügen', 'sondern in die Spalte "legen" damit es proportional mitskaliert.'),
                    'inputType' => 'checkbox',
                    'dependsOn' => array(
                        'field' => 'contentType',
                        'value' => '1',
                    ),
                ),

                'image_both' => array(
                    'label' => array('Bild als Hintergrund für beide Spalten', 'falls ein Bild in der linken Spalte zugeordnet ist, liegt es über diesem Bild'),
                    'inputType' => 'checkbox',
                    'dependsOn' => array(
                        'field' => 'contentType',
                        'value' => '1',
                    ),
                ),


                'animation_type_image_col' => array(
            'label' => array(
                'de' => array('Bildspalte: Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => array(
                /* Fading entrances  */
                'animate__fadeIn' => 'fadeIn ',
                'no-animation' => 'Keine Animation',
                'animate__fadeInUp' => 'fadeInUp',
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


                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                        'tl_class' => 'clr'
                    ),
                    'dependsOn' => array(
                        'field' => 'contentType',
                        'value' => '1',
                    ),
                ),

                'size_right' => array(
                    'label' => array('Bildbreite und Bildhöhe', ''),
                    'inputType' => 'imageSize',
                    'options' => System::getImageSizes(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                    ),
                ),


                'content_rightcol' => array(
                    'label' => array('Extra Textfeld für rechte Spalte', 'Liegt auf dem Bild'),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                    'dependsOn' => array(
                        'field' => 'contentType',
                        'value' => '1',
                    ),
                ),

                'alternate_image' => array(
                    'label' => array('Code als alternative zum Bild in Spalte anzeigen', 'z. B. Googlemap-Frame'),
                    'inputType' => 'textarea',
                    'dependsOn' => array(
                        'field' => 'contentType',
                        'value' => '2',
                    ),
                ),

                'video' => array(
                    'label' => array('Video', 'Dateiformat: mp4'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'mp4',
                        'tl_class' => 'clr'
                    ),
                    'dependsOn' => array(
                        'field' => 'contentType',
                        'value' => '3',
                    ),
                ),
            ),
        ),
    ),
);
