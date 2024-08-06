<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Anker-Navigation (onepagenavi)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
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


        'nav_style' => array(
            'label' => array(
                'de' => array('Text-Darstellungstyp', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'style-1' => 'Style 1: Navigation erscheint vertikal nach dem scrollen und ist fixiert',
                'style-2' => 'Style 2: Navigation befindet sich horizontal innerhalb eines Artikels und scrollt nach Berührung mit',
            ),
            'eval' => array('tl_class' => 'clr'),
        ),

        'background_color' => array(
            'label' => array('Hintergrundfarbe', 'Standard: Hauptfarbe'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'text_color' => array(
            'label' => array('Schriftfarbe', 'Standard: Weiß'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'offset' => array(
            'label' => array('Wie viel PX soll gescrollt werden,  bis die Navigation sichtbar wird', 'Standard: 300'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'nav_style',
                'value' => 'style-1',
            ),
            'eval' => array('tl_class' => 'clr'),
        ),


        'smaller_containers' => array(
            'label' => array('"Container" auf der Seite schmäler machen, damit OnepageNavi den Content nicht überlagert', 'Greift nicht bei Elementen die auf die volle Breite gehen'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'nav_style',
                'value' => 'style-1',
            ),
        ),


        'add_totopbutton' => array(
            'label' => array('"Nach oben"-Button hinzufügen', 'Bei Style-2 wird der Button nur Mobile ein- bzw. ausgeblendet'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'hide_mobile' => array(
            'label' => array('Auf dem Handy ausblenden', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'urls' => array(
            'label' => array('Link', ''),
            'elementLabel' => '%s. Link',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'eval' => array('tl_class' => 'clr'),
            'fields' => array(
                'text' => array(
                    'label' => array('Bezeichnung', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'link' => array(
                    'label' => array('Link', 'Anker-ID eingeben, z. B. "#anker"'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
        ),
    ),
);
