<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Text-Slider mit Bild + Text je Slide (contentslider)', ''),
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
        ),
        'subline' => array(
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


        'is_fullwidth' => array(
            'label' => array('Element auf volle Breite', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'nav_position' => array(
            'label' => array(
                'de' => array('Position der Navigation', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                '1' => 'Oberhalb',
                '2' => 'Unterhalb',
                '3' => 'Links',
                '4' => 'Rechts',
            ),
        ),

        'element_type' => array(
            'label' => array('Darstellungstyp', ''),
            'inputType' => 'radio',
            'options' => array(
                'is_slider' => 'Darstellungstyp 1: Slider',
                'is_buttons' => 'Darstellungstyp 2: Verlinkungen',
            ),
        ),


        'fixed_image' => array(
            'label' => array('Fixes Bild', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
            ),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_buttons',
            ),
        ),

        'size_fixed' => array(
            'label' => array('Bildbreite und Bildhöhe', ''),
            'inputType' => 'imageSize',
            'options' => System::getImageSizes(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
            ),

            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_buttons',
            ),
        ),


        'settings_slider' => array(
            'label' => array('Slider-Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),


        'slide_effect' => array(
            'label' => array(
                'de' => array('Slide-Effekt', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'slide' => 'Slide (Standard)',
                'fade' => 'Fade',
                'coverflow' => 'Coverflow',
                'flip' => 'Flip',
                'cube' => 'Cube',

            ),
            'eval' => array('tl_class' => 'w50'),
        ),


        'transition_time' => array(
            'label' => array('Animationszeit in ms', 'Standard: 500'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'show_pagination' => array(
            'label' => array('Paginierung anzeigen', 'mittig unter dem Slider, in Form von Punkten'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'show_arrows' => array(
            'label' => array('Pfeile anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'mousewheel' => array(
            'label' => array('Slider-Navigation per Mausrad aktivieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),


        'loop' => array(
            'label' => array('Automatisch wieder von Anfang starten', '"loop"'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),


        'autoplay' => array(
            'label' => array('Autoplay aktivieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'autoplay_time' => array(
            'label' => array('Autoplay-Zyklus', 'nach wie viel MS soll zum nächsten Slide gewechselt werden, Standard: 3000'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'autoplay',
            ),
        ),


        'elements' => array(
            'label' => array('Elemente', ''),
            'elementLabel' => '%s. Element',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(

                'slide_nav_text' => array(
                    'label' => array('Text für Navigation', ''),
                    'inputType' => 'text',
                ),


                'settings_2' => array(
                    'label' => array('Bild (Nur relevant wenn "Darstellungstyp 1")', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),

                ),

                'row_reverse' => array(
                    'label' => array('Spalten umkehren', 'Dadurch ist die Bildspalte rechts'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),


                'column_width' => array(
                    'label' => array(
                        'de' => array('Breite der Bildspalte', 'Nur relevant wenn Navigationsposition oberhalb oder unterhalb'),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12 col-md-6 col-lg-3' => '25%',
                        'col-12 col-md-6 col-lg-4' => '33%',
                        'col-12 col-md-6' => '50%',
                        'col-12 col-md-6 col-lg-8' => '66.66%',
                        'col-12 col-md-6 col-lg-9' => '75%',
                        'col-12' => 'Volle Breite',
                        'col-12 col-md' => 'Automatische Breite (füllend)',
                        'col-12 col-md-auto' => 'Breite anhand des Inhalts',
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
                    )
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


                'settings_3' => array(
                    'label' => array('Text (Nur relevant wenn "Darstellungstyp 1")', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),
                ),


                'topline' => array(
                    'label' => array('Topline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'text' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
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
                        'h6' => 'H6',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),


                'text' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                     'eval' => array('tl_class' => 'clr'),

                ),


                'longtext' => array(
                    'label' => array('Beschreibung', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),


                'settings_url' => array(
                    'label' => array('Verlinkung (Nur relevant wenn "Darstellungstyp 2")', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),

                ),

                'link_url' => array(
                    'label' => array('Verlinkung'),
                    'inputType' => 'url',
                ),

                'link_newtab' => array(
                    'label' => array('Link in neuen Tab öffnen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
        ),
    ),
);
