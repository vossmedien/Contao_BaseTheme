<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | 50% Box & Boxen auf gleicher Höhe (equalboxesrows)', ''),
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
        'row' => array(
            'label' => array('Zeilen', ''),
            'elementLabel' => '%s. Zeile',
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
                'swapcolumns' => array(
                    'label' => array('Spalten tauschen', ''),
                    'inputType' => 'checkbox',
                ),
                'main_image' => array(
                    'label' => array('Boxen-Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => true,
                        'fieldType' => 'checkbox',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',

                    ),
                ),

                'size' => array(
                    'label' => array('Bildbreite und Bildhöhe', ''),
                    'inputType' => 'imageSize',
                    'options' => System::getImageSizes(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                    ),
                ),


                'main_url' => array(
                    'label' => array('Link', ''),
                    'inputType' => 'url',
                ),

                'link_newtab' => array(
                    'label' => array('Link in neuen Tab öffnen', ''),
                    'inputType' => 'checkbox',
                ),

                'main_subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'main_topline' => array(
                    'label' => array('Topline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'main_headline_type' => array(
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
                        'h6' => 'H6',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),


                'main_headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
                ),
                'main_onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),

                'main_content' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),
                'boxes' => array(
                    'label' => array('Boxen (rechts)', ''),
                    'elementLabel' => '%s. Box',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 4,
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
                            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
                        ),
                        'image' => array(
                            'label' => array('Boxen-Bild', ''),
                            'inputType' => 'fileTree',
                            'eval' => array(
                                'multiple' => true,
                                'fieldType' => 'checkbox',
                                'filesOnly' => true,
                                'extensions' => 'jpg,jpeg,png,svg,webp',

                            ),
                        ),

                        'size' => array(
                            'label' => array('Bildbreite und Bildhöhe', ''),
                            'inputType' => 'imageSize',
                            'options' => System::getImageSizes(),
                            'reference' => &$GLOBALS['TL_LANG']['MSC'],
                            'eval' => array(
                                'rgxp' => 'digit',

                                'includeBlankOption' => true,
                            ),
                        ),

                        'url' => array(
                            'label' => array('Link', ''),
                            'inputType' => 'url',

                        ),

                        'link_newtab' => array(
                            'label' => array('Link in neuen Tab öffnen', ''),
                            'inputType' => 'checkbox',

                        ),


                        'topline' => array(
                            'label' => array('Topline', 'Text oberhalb der Überschrift'),
                            'inputType' => 'text',
                            'eval' => array('tl_class' => 'w50'),
                        ),
                        'subline' => array(
                            'label' => array('Subline', 'Text unterhalb der Überschrift'),
                            'inputType' => 'text',
                            'eval' => array('tl_class' => 'w50'),
                        ),


                        'headline' => array(
                            'label' => array('Überschrift', ''),
                            'inputType' => 'text',
                            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
                        ),

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
                            'eval' => array('tl_class' => 'w50'),
                        ),

                        'onlystyle' => array(
                            'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                            'inputType' => 'checkbox',
                            'eval' => array('tl_class' => 'clr'),
                        ),

                        'content' => array(
                            'label' => array('Text', ''),
                            'inputType' => 'textarea',
                            'eval' => array('rte' => 'tinyMCE'),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
