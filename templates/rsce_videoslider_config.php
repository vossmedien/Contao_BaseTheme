<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Slider mit Video, optional in modaler Box', ''),
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

        'fullwidth' => array(
            'label' => array('Boxen auf die volle Breite des Viewports anzeigen', 'Funktioniert nicht innerhalb von Elementen'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
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


        'size' => array(
            'label' => array('Bildbreite und Bildhöhe', 'Für gleichgroße Slides'),
            'inputType' => 'imageSize',
            'options' => System::getImageSizes(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'tl_class' => 'clr',
                'rgxp' => 'digit',
                'includeBlankOption' => true,
            ),
        ),

        'columns' => array(
            'label' => array(
                'de' => array('Anzahl der Spalten im Slider', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                '1' => '1 Spaltig',
                '2' => '2 Spaltig',
                '3' => '3 Spaltig',
                '4' => '4 Spaltig',
                '6'
                => '6 Spaltig',
            ),
            'eval' => array('tl_class' => 'w50'),
        ),


        'gutter' => array(
            'label' => array('Abstand zwischen den Slides', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

             'slide_effect' => array(
            'label' => array(
                'de' => array('Slide-Effekt', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'slide' => 'Slide (Standard)',
                'coverflow' => 'Coverflow',
                'fade' => 'Fade',
                'flip' => 'Flip',
                'cube' => 'Cube',

            ),
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

        /*
        'centered_slides' => array(
            'label' => array('Slides passe', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),
        */

        'loop' => array(
            'label' => array('Automatisch wieder von Anfang starten', '"loop" funktioniert derzeit nur, wenn mindestens doppelt so viele Elemente pro "Seite" sichtbar sind (Anzahl der Spalten 3 = Mindestanzahl = 6)'),
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


                'video_method' => array(
                    'label' => array('Video... ', ''),
                    'inputType' => 'radio',
                    'options' => array(
                        'modal' => '...in modalem Fenster darstellen',
                        'inline' => '...innerhalb der Box einbinden',
                        'new_tab' => '...in neuem Tab öffnen',
                    ),
                    'eval' => array(
                        'mandatory' => true,
                    ),
                ),

                'video_type' => array(
                    'label' => array('Video... ', ''),
                    'inputType' => 'radio',
                    'options' => array(
                        'local_file' => 'Lokale Datei auswählen',
                        'youtube' => 'Youtube-Video',
                    ),
                    'eval' => array(
                        'mandatory' => true,
                    ),
                ),


                'video_id' => array(
                    'label' => array('Youtube-Video ID', 'in der URL nach /watch?v='),
                    'inputType' => 'text',
                    'tl_class' => 'clr',

                    'eval' => array(
                        'mandatory' => true,
                    ),

                    'dependsOn' => array(
                        'field' => 'video_type',  // Name des Feldes das geprüft werden soll
                        'value' => 'youtube',      // Der Wert der mit dem Feldwert übereinstimmen muss
                    ),
                ),


                'video_path' => array(
                    'label' => array('Video', 'Video-Format: MP4'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'mp4',
                        'tl_class' => 'clr',
                        'mandatory' => true,
                    ),

                    'dependsOn' => array(
                        'field' => 'video_type',  // Name des Feldes das geprüft werden soll
                        'value' => 'local_file',      // Der Wert der mit dem Feldwert übereinstimmen muss
                    ),
                ),

                'image' => array(
                    'label' => array('Vorschaubild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                    'dependsOn' => array(
                        'field' => 'video_method',
                        'value' => ['modal', 'new_tab'],
                    ),
                ),

                'text' => array(
                    'label' => array('Bezeichnung', ''),
                    'inputType' => 'text',
                ),

                'longtext' => array(
                    'label' => array('Beschreibung', ''),
                    'inputType' => 'text',
                    'tl_class' => 'clr',
                    'eval' => array('allowHtml' => true),
                ),
            ),
        ),
    ),
);
