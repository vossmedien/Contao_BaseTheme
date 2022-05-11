<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Boxen mit Bild, Text, Verlinkung, ggf. modales Fenster (linkboxen)', ''),
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


        'boxes' => array(
            'label' => array('Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 99,
            'fields' => array(

                'settings_1' => array(
                    'label' => array('Einstellungen', ''),
                    'inputType' => 'group',
                ),

                'box_style' => array(
                    'label' => array(
                        'de' => array('Darstellungstyp der Boxen', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'style-1' => 'Typ 1: Abgerundete Ecken, Schatten, zentriert',
                        'style-2' => 'Typ 2: Grauer Hintergrund, linksbündig, Pfeil auf rechter Seite',
                    ),
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
                    'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
                ),
                'column_width' => array(
                    'label' => array(
                        'de' => array('Spaltenbreite', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12 col-md-6 col-xl-3' => '25%',
                        'col-12 col-md-6 col-lg-4' => '33%',
                        'col-12 col-md-6' => '50%',
                        'col-12 col-lg-8' => '66.66%',
                        'col-12 col-lg-9' => '75%',
                        'col-12' => 'Volle Breite',
                        'col-12 col-md-auto' => 'Automatische Breite (füllend)',
                        'col-12 col-md' => 'Breite anhand des Inhalts',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),

                'linkoverlay' => array(
                    'label' => array('', 'Die Button-Liste wird daraufhin ausgeblendet und es wird nur noch der erste Button berücksichtigt'),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Den ersten Button oder die Modal-Verlinkung ohne Button anzeigen, sondern die Verlinkung nach Klick auf Box öffnen',
                    ),
                    'eval' => array('tl_class' => 'clr'),
                ),

                'hide_arrow' => array(
                    'label' => array('', '"Link-Pfeil" neben Headline ausblenden'),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => '"Link-Pfeil" neben Headline ausblenden',
                    ),
                    'dependsOn' => array(
                        'field' => 'linkoverlay',
                        'value' => '1',
                    ),
                ),

                'alternate_background_hover' => array(
                    'label' => array('Hoverfarbe für Hintergrund', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'linkoverlay',
                        'value' => '1',
                    ),
                ),
                'alternate_textcolor_hover' => array(
                    'label' => array('Hoverfarbe für Text', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'linkoverlay',
                        'value' => '1',
                    ),
                ),


                'settings_image' => array(
                    'label' => array('Bild / Video', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),
                ),


                'image' => array(
                    'label' => array('Bild / Video', 'Video-Format: MP4'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,mp4,webm,ogv',
                    ),
                ),


                'not_as_bg' => array(
                    'label' => array('Bild nicht als Hintergrund sondern als skalierbares Bild einfügen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),


                'settings_3' => array(
                    'label' => array('Inhalte', ''),
                    'inputType' => 'group',
                ),


                'text' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'as_box' => array(
                    'label' => array('Überschrift und Subline in Box mit Schatten darstellen', ''),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Überschrift und Subline in Box mit Schatten darstellen',
                    ),
                    'eval' => array('tl_class' => 'clr'),
                ),


                'shadowbox_background' => array(
                    'label' => array('Alternative Hintergrundfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'as_box',
                        'value' => '1',
                    ),
                ),
                'shadowbox_textcolor' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'as_box',
                        'value' => '1',
                    ),
                ),


                'is_map' => array(
                    'label' => array('Text oder iFrame einbinden ', ''),
                    'inputType' => 'select',
                    'options' => array(
                        'text' => 'Text einbinden',
                        'code' => 'Ein iFrame, z. B. eine Google-Map einbinden',
                    ),
                    'eval' => array('tl_class' => 'clr'),
                ),

                'longtext' => array(
                    'label' => array('Langtext', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),

                    'dependsOn' => array(
                        'field' => 'is_map',
                        'value' => 'text',
                    ),
                ),

                'code' => array(
                    'label' => array('Code', 'z. B. Googlemap-Frame'),
                    'inputType' => 'textarea',
                    'dependsOn' => array(
                        'field' => 'is_map',
                        'value' => 'code',
                    ),
                ),


                'add_collapsableBox' => array(
                    'label' => array('', ''),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Ausklappbare Box inkl. zweitem Bild und einstellbarer Option für begrenzbaren Langtext hinzufügen',
                    ),
                    'eval' => array('tl_class' => 'clr'),
                ),


                'expand_longtext' => array(
                    'label' => array('Langtext-Einstellungen', ''),
                    'inputType' => 'radio',
                    'options' => array(
                        'show_all' => 'Langtext komplett anzeigen',
                        'show_teaser' => 'Langtext beschränken und nach Hover aufklappen, ansonsten wird kompletter Langtext angezeigt',
                        'hide_preview' => 'Langtext eingangs komplett ausblenden und nur bei Hover anzeigen',
                    ),

                    'dependsOn' => array(
                        'field' => 'add_collapsableBox',
                        'value' => '1',
                    ),

                    'eval' => array(
                        'mandatory' => true,
                    ),
                ),


                'expand_image' => array(
                    'label' => array('Bild für Ausklapp-Box', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                    'dependsOn' => array(
                        'field' => 'add_collapsableBox',
                        'value' => '1',
                    ),
                ),

                'settings_text' => array(
                    'label' => array('Text-Formatierungen', ''),
                    'inputType' => 'group',
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
                ),

                'alternate_background' => array(
                    'label' => array('Alternative Hintergrundfarbe', 'In HEX oder rgb(a) angeben, für Darstellungstyp 2 (Standard: Hellgrau)'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'alternate_textcolor' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'settings_addItems' => array(
                    'label' => array('Buttons oder Modales Fenster hinzufügen', ''),
                    'inputType' => 'group',
                ),


                'add_items' => array(
                    'label' => array('Folgendes hinzufügen', ''),
                    'inputType' => 'select',
                    'options' => array(
                        '0' => 'Nichts',
                        'buttons' => 'Buttons',
                        'modal' => 'Modales Fenster',
                    ),
                ),

                'settings_5' => array(
                    'label' => array('Modales Fenster', ''),
                    'inputType' => 'group',

                    'dependsOn' => array(
                        'field' => 'add_items',
                        'value' => 'modal',
                    ),
                ),

                'modal_button_animation' => array(
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
                'modal_button_text' => array(
                    'label' => array('Link-Beschriftung', ''),
                    'inputType' => 'text',
                ),
                'modal_button_type' => array(
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
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),
                'modal_button_size' => array(
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

                'modal_image' => array(
                    'label' => array('Bild für modales Fenster', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                        'tl_class' => 'clr'
                    ),

                ),
                'modal_longtext' => array(
                    'label' => array('Text für modales Fenster', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),

                'modal_backgroundcolor' => array(
                    'label' => array('Hintergrundfarbe für das gesamte Modal', 'in HEX oder RGB angeben, Standard: Weiß'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'modal_size' => array(
                    'label' => array(
                        'de' => array('Größe des modalen Fensters', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        '' => 'Standard',
                        'modal-sm' => 'Klein',
                        'modal-lg' => 'Groß',
                        'modal-xl' => 'Sehr groß',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),
                'modal_animation_type' => array(
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
                    'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
                ),

                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 20,
                    'eval' => array('tl_class' => 'clr'),

                    'dependsOn' => array(
                        'field' => 'add_items',
                        'value' => 'buttons',
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
                        ),
                        'link_betreff' => array(
                            'label' => array('Betreffzeile für "mailto:"-Buttons', '(optional, falls Link eine neue Email öffnen soll)'),
                            'inputType' => 'text',
                            'eval' => array('tl_class' => 'w50'),
                        ),
                        'link_url' => array(
                            'label' => array('Verlinkung der Beschriftung', ''),
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
                        ),
                    ),
                ),
            ),
        ),
    ),
);
