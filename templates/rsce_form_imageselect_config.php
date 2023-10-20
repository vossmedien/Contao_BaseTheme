<?php
// rsce_boxes_config.php
return array(
    'label' => array('Custom | Formular :: Image-Checkbox mit Headine, Text, Bild & Preis', ''),
    'types' => array('form'),
    'contentCategory' => 'texts',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => array(
                'options' => array(
                    'no-animation' => 'Keine Animation',
                    'animate__fadeInUp' => 'fade-up (Standard)',
                    'animate__fade' => 'fade',
                    'animate__fadeDown' => 'fade-down',
                    'animate__fadeLeft' => 'fade-left',
                    'animate__fadeRight' => 'fade-right',
                    'animate__fadeUpRight' => 'fade-up-right',
                    'animate__fadeUpLeft' => 'fade-up-left',
                    'animate__fadeDownRight' => 'fade-down-right',
                    'animate__fadeDownLeft' => 'fade-down-left',
                    'animate__flipUp' => 'flip-up',
                    'animate__flipDown' => 'flip-down',
                    'animate__flipLeft' => 'flip-left',
                    'animate__flipRight' => 'flip-right',
                    'animate__slideUp' => 'slide-up',
                    'animate__slideDown' => 'slide-down',
                    'animate__slideLeft' => 'slide-left',
                    'animate__slideRight' => 'slide-right',
                    'animate__zoomIn' => 'zoom-in',
                    'animate__zoomInUp' => 'zoom-in-up',
                    'animate__zoomInDown' => 'zoom-in-down',
                    'animate__zoomInLeft' => 'zoom-in-left',
                    'animate__zoomInRight' => 'zoom-in-right',
                    'animate__zoomOut' => 'zoom-out',
                    'animate__zoomOutUp' => 'zoom-out-up',
                    'animate__zoomOutDown' => 'zoom-out-down',
                    'animate__zoomOutLeft' => 'zoom-out-left',
                    'animate__zoomOutRight' => 'zoom-out-right'
                ),
            ),
        ),
        'headline' => array(
            'label' => array('Headine', ''),
            'inputType' => 'text',
        ),
        'headline_type' => array(
            'label' => array(
                'de' => array('Typ der Überschrift', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'h1' => 'H1 (Haupt-Headline für SEO, darf nur 1x vorkommen)',
                'h2' => 'H2 (Sollte H1 thematisch untergeordnet sein)',
                'h3' => 'H3 (Sollte H2 thematisch untergeordnet sein)',
                'h4' => 'H4',
                'h5' => 'H5',
            ),
        ),
        'topline' => array(
            'label' => array('Topline', 'Text oberhalb der Überschrift'),
            'inputType' => 'text',
        ), 'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
        ),
        'input_type' => array(
            'label' => array(
                'de' => array('Input-Typ', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'checkbox' => 'Checkbox',
                'radio' => 'Radio',
            ),
        ),
        'name' => array(
            'inputType' => 'standardField',
        ),
        'text_right' => array(
            'label' => array('Text rechts als Box neben den Listing-Elementen', ''),
            'inputType' => 'text',
            'eval' => array(
                'allowHtml' => true,
                'rte' => 'tinyMCE',
                'tl_class' => 'clr'
            ),
        ),
        'inputs' => array(
            'label' => array('Inputs', ''),
            'elementLabel' => '%s. Input',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 99,
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
                'input_value' => array(
                    'label' => array('Eindeutiger Wert des Inputs', 'einmalig, keine Sonderzeichen'),
                    'inputType' => 'text',
                ),
                'headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                ),
                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'h1' => 'H1 (Haupt-Headline für SEO, darf nur 1x vorkommen)',
                        'h2' => 'H2 (Sollte H1 thematisch untergeordnet sein)',
                        'h3' => 'H3 (Sollte H2 thematisch untergeordnet sein)',
                        'h4' => 'H4',
                        'h5' => 'H5',
                    ),
                ),
                'text_top' => array(
                    'label' => array('Text oberhalb des Bildes', ''),
                    'inputType' => 'text',
                    'eval' => array(
                        'allowHtml' => true,
                        'rte' => 'tinyMCE',
                        'tl_class' => 'clr'
                    ),
                ),
                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    ),
                ),
                'text_bottom' => array(
                    'label' => array('Text unterhalb des Bildes', ''),
                    'inputType' => 'text',
                    'eval' => array(
                        'allowHtml' => true,
                        'rte' => 'tinyMCE',
                        'tl_class' => 'clr'
                    ),
                ),
                'price' => array(
                    'label' => array('Preis ohne Zeichen', 'das Währungssymbol wird automatisch angehängt'),
                    'inputType' => 'text',
                ),
                'preview_links' => array(
                    'label' => array('Text unterhalb des Preises', ''),
                    'inputType' => 'text',
                    'eval' => array(
                        'allowHtml' => true,
                        'rte' => 'tinyMCE',
                        'tl_class' => 'clr'
                    ),
                ),
            ),
        ),
    ),
);
