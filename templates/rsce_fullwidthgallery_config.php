<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Galerie auf voller Breite mit Hover-Text & Verlinkung (fullwidthgallery)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'topline' => array(
            'label' => array('Topline', 'Text oberhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => array(
                /* Fading entrances  */
                'animate__fadeIn' => 'fadeIn (Meistens Standard)',
                'no-animation' => 'Keine Animation',
                'animate__fadeInUp' => 'fadeInUp ',
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


        'show_icon' => array(
            'label' => array('Info-Icon oben rechts anzeigen, falls Hover-Inhalte vorhanden sind', 'Um zu symbolisieren, dass hier nach Hover Inhalte existieren'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'gallery' => array(
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
                        'animate__fadeIn' => 'fadeIn (Meistens Standard)',
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
                    'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
                ),

                'column_width' => array(
                    'label' => array(
                        'de' => array('Spaltenbreite', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12 col-md-6 col-lg-3' => '25%',
                        'col-12 col-md-6 col-lg-4' => '33%',
                        'col-12 col-md-6' => '50%',
                        'col-12 col-md-6 col-lg-8' => '66.66%',
                        'col-12 col-md-6 col-lg-9' => '75%',
                        'col-12' => 'Volle Breite',
                    ),
                    'eval' => array(
                        'tl_class' => 'w50'
                    ),
                ),


                'settings_image' => array(
                    'label' => array('Bildeinstellungen', ''),
                    'inputType' => 'group',
                        'eval' => array('collapsible' => true, 'collapsed' => true),
                ),

                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                        'tl_class' => 'clr',
                        'mandatory' => true,
                    ),
                ),

                'size' => array(
                    'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
                    'inputType' => 'imageSize',
                    'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                        'tl_class' => ' clr'
                    ),
                ),

                'show_initial_content' => array(
                    'label' => array('Inhalt initial auf Bild anzeigen', ''),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Inhalt initial auf Bild anzeigen',
                    ),
                ),
                'show_contents' => array(
                    'label' => array('', ''),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Inhalte nach Hover anzeigen',
                    ),
                ),


                'settings_headline' => array(
                    'label' => array('Text auf Bild', ''),
                    'inputType' => 'group',
                    'dependsOn' => array(
                        'field' => 'show_initial_content',
                        'value' => '1',
                    ),
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

                'background_color' => array(
                    'label' => array('Hintergrundfarbe für Inhalt', 'Standard: Transparent'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'text_color' => array(
                    'label' => array('Schriftfarbe', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'topline' => array(
                    'label' => array('Topline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
                ),

                'subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'headline' => array(
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

                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),


                'settings_hover' => array(
                    'label' => array('Hover-Inhalte', ''),
                    'inputType' => 'group',
                    'dependsOn' => array(
                        'field' => 'show_contents',
                        'value' => '1',
                    ),
                ),


                'show_effect' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation für Hover-Content', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        /* Fading entrances  */
                        'fadeIn' => 'fadeIn (Standard)',
                        'no-animation' => 'Keine Animation',
                        'fadeInUp' => 'fadeInUp',
                        'fadeInDown' => 'fadeInDown',
                        'fadeInDownBig' => 'fadeInDownBig',
                        'fadeInLeft' => 'fadeInLeft',
                        'fadeInLeftBig' => 'fadeInLeftBig',
                        'fadeInRight' => 'fadeInRight',
                        'fadeInRightBig' => 'fadeInRightBig',
                        'fadeInUpBig' => 'fadeInUpBig',
                        'fadeInTopLeft' => 'fadeInTopLeft',
                        'fadeInTopRight' => 'fadeInTopRight',
                        'fadeInBottomLeft' => 'fadeInBottomLeft',
                        'fadeInBottomRight' => 'fadeInBottomRight',
                        /* Attention seekers  */
                        'bounce' => 'bounce',
                        'flash' => 'flash',
                        'pulse' => 'pulse',
                        'rubberBand' => 'rubberBand',
                        'shakeX' => 'shakeX',
                        'shakeY' => 'shakeY',
                        'headShake' => 'headShake',
                        'swing' => 'swing',
                        'tada' => 'tada',
                        'wobble' => 'wobble',
                        'jello' => 'jello',
                        'heartBeat' => 'heartBeat',
                        /* Back entrances */
                        'backInDown' => 'backInDown',
                        'backInLeft' => 'backInLeft',
                        'backInRight' => 'backInRight',
                        'backInUp' => 'backInUp',
                        /* Bouncing entrances  */
                        'bounceIn' => 'bounceIn',
                        'bounceInDown' => 'bounceInDown',
                        'bounceInLeft' => 'bounceInLeft',
                        'bounceInRight' => 'bounceInRight',
                        'bounceInUp' => 'bounceInUp',
                        /* Flippers */
                        'flip' => 'flip',
                        'flipInX' => 'flipInX',
                        'flipInY' => 'flipInY',
                        /* Lightspeed */
                        'lightSpeedInRight' => 'lightSpeedInRight',
                        'lightSpeedInLeft' => 'lightSpeedInLeft',
                        /* Rotating entrances */
                        'rotateIn' => 'rotateIn',
                        'rotateInDownLeft' => 'rotateInDownLeft',
                        'rotateInDownRight' => 'rotateInDownRight',
                        'rotateInUpLeft' => 'rotateInUpLeft',
                        'rotateInUpRight' => 'rotateInUpRight',
                        /* Specials */
                        'hinge' => 'hinge',
                        'jackInTheBox' => 'jackInTheBox',
                        'rollIn' => 'rollIn',
                        /* Zooming entrances */
                        'zoomIn' => 'zoomIn',
                        'zoomInDown' => 'zoomInDown',
                        'zoomInLeft' => 'zoomInLeft',
                        'zoomInRight' => 'zoomInRight',
                        'zoomInUp' => 'zoomInUp',
                        /* Sliding entrances */
                        'slideInDown' => 'slideInDown',
                        'slideInLeft' => 'slideInLeft',
                        'slideInRight' => 'slideInRight',
                        'slideInUp' => 'slideInUp',
                    ),
                    'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
                ),


                'hover_textalign' => array(
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


                'hover_background_color' => array(
                    'label' => array('Hintergrundfarbe für Inhalt', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'hover_text_color' => array(
                    'label' => array('Schriftfarbe', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'hover_topline' => array(
                    'label' => array('Topline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
                ),

                'hover_subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'hover_headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'hover_headline_type' => array(
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

                'hover_onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),


                'desc' => array(
                    'label' => array('Text', 'sichtbar nach Hover'),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                    'dependsOn' => array(
                        'field' => 'show_contents',
                        'value' => '1',
                    ),
                ),

                'box_link_type' => array(
                    'label' => array('Verlinkung der Box', ''),
                    'inputType' => 'radio',
                    'options' => array(
                        '1' => 'Ganze Box verlinken',
                        '2' => 'Buttons anzeigen',
                    ),
                    'default' => 1
                ),

                'link' => array(
                    'label' => array('Link', ''),
                    'inputType' => 'url',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'box_link_type',
                        'value' => '1',
                    ),
                ),

                'buttons' => array(
                    'label' => array('Button', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 10,
                    'fields' => array(
                        'animation_type' => array(
                            'label' => array(
                                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                            ),
                            'inputType' => 'select',
                            'options' => array(
                                /* Fading entrances  */
                                'fadeInUp' => 'fadeInUp (Meistens Standard)',
                                'no-animation' => 'Keine Animation',
                                'fadeIn' => 'fadeIn',
                                'fadeInDown' => 'fadeInDown',
                                'fadeInDownBig' => 'fadeInDownBig',
                                'fadeInLeft' => 'fadeInLeft',
                                'fadeInLeftBig' => 'fadeInLeftBig',
                                'fadeInRight' => 'fadeInRight',
                                'fadeInRightBig' => 'fadeInRightBig',
                                'fadeInUpBig' => 'fadeInUpBig',
                                'fadeInTopLeft' => 'fadeInTopLeft',
                                'fadeInTopRight' => 'fadeInTopRight',
                                'fadeInBottomLeft' => 'fadeInBottomLeft',
                                'fadeInBottomRight' => 'fadeInBottomRight',
                                /* Attention seekers  */
                                'bounce' => 'bounce',
                                'flash' => 'flash',
                                'pulse' => 'pulse',
                                'rubberBand' => 'rubberBand',
                                'shakeX' => 'shakeX',
                                'shakeY' => 'shakeY',
                                'headShake' => 'headShake',
                                'swing' => 'swing',
                                'tada' => 'tada',
                                'wobble' => 'wobble',
                                'jello' => 'jello',
                                'heartBeat' => 'heartBeat',
                                /* Back entrances */
                                'backInDown' => 'backInDown',
                                'backInLeft' => 'backInLeft',
                                'backInRight' => 'backInRight',
                                'backInUp' => 'backInUp',

                                /* Bouncing entrances  */
                                'bounceIn' => 'bounceIn',
                                'bounceInDown' => 'bounceInDown',
                                'bounceInLeft' => 'bounceInLeft',
                                'bounceInRight' => 'bounceInRight',
                                'bounceInUp' => 'bounceInUp',
                                /* Bouncing exits  */
                                'bounceOut' => 'bounceOut',
                                'bounceOutDown' => 'bounceOutDown',
                                'bounceOutLeft' => 'bounceOutLeft',
                                'bounceOutRight' => 'bounceOutRight',
                                'bounceOutUp' => 'bounceOutUp',
                                /* Fading exits */
                                'fadeOut' => 'fadeOut',
                                'fadeOutDown' => 'fadeOutDown',
                                'fadeOutDownBig' => 'fadeOutDownBig',
                                'fadeOutLeft' => 'fadeOutLeft',
                                'fadeOutLeftBig' => 'fadeOutLeftBig',
                                'fadeOutRight' => 'fadeOutRight',
                                'fadeOutRightBig' => 'fadeOutRightBig',
                                'fadeOutUp' => 'fadeOutUp',
                                'fadeOutUpBig' => 'fadeOutUpBig',
                                'fadeOutTopLeft' => 'fadeOutTopLeft',
                                'fadeOutTopRight' => 'fadeOutTopRight',
                                'fadeOutBottomRight' => 'fadeOutBottomRight',
                                'fadeOutBottomLeft' => 'fadeOutBottomLeft',
                                /* Flippers */
                                'flip' => 'flip',
                                'flipInX' => 'flipInX',
                                'flipInY' => 'flipInY',
                                /* Lightspeed */
                                'lightSpeedInRight' => 'lightSpeedInRight',
                                'lightSpeedInLeft' => 'lightSpeedInLeft',
                                /* Rotating entrances */
                                'rotateIn' => 'rotateIn',
                                'rotateInDownLeft' => 'rotateInDownLeft',
                                'rotateInDownRight' => 'rotateInDownRight',
                                'rotateInUpLeft' => 'rotateInUpLeft',
                                'rotateInUpRight' => 'rotateInUpRight',
                                /* Specials */
                                'hinge' => 'hinge',
                                'jackInTheBox' => 'jackInTheBox',
                                /* Zooming entrances */
                                'zoomIn' => 'zoomIn',
                                'zoomInDown' => 'zoomInDown',
                                'zoomInLeft' => 'zoomInLeft',
                                'zoomInRight' => 'zoomInRight',
                                'zoomInUp' => 'zoomInUp',
                                /* Sliding entrances */
                                'slideInDown' => 'slideInDown',
                                'slideInLeft' => 'slideInLeft',
                                'slideInRight' => 'slideInRight',
                                'slideInUp' => 'slideInUp',
                            ),
                            'eval' => array('chosen' => 'true')
                        ),
                        'link_text' => array(
                            'label' => array(
                                'de' => array('Button-Beschriftung', 'Button befindet sich rechts unter dem Text'),
                            ),
                            'inputType' => 'text',
                        ),
                        'link_url' => array(
                            'label' => array('Verlinkung', 'z . B . mailto:info@domain.de'),
                            'inputType' => 'url',
                        ),

                        'new_tab' => array(
                            'label' => array('Link in neuen Tab öffnen', ''),
                            'inputType' => 'checkbox',
                            'eval' => array('tl_class' => 'clr'),
                        ),

                        'link_betreff' => array(
                            'label' => array('Betreffzeile für "mailto:" - Buttons', '(optional, falls Link eine neue Email öffnen soll)'),
                            'inputType' => 'text',
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
                                'btn-tertiary' => 'Tertiär - Farbe',
                                'btn-outline-tertiary' => 'Tertiär - Farbe(Outline)',
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
                                'btn-xl' => 'Sehr groß',
                            ),
                            'eval' => array('tl_class' => 'w50'),
                        ),
                    ),
                    'dependsOn' => array(
                        'field' => 'box_link_type',
                        'value' => '2',
                    ),
                ),


            ),
        ),
    ),
);
