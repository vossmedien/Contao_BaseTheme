<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
$config = array(
    'label' => array('Custom | Bildwechsler mit Beschreibungstext (colorpalettes)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'standardFields' => array('headline', 'cssID'),
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(

        'topline' => array(
            'label' => array('Topline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),

        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),


        'settings_1' => array(
            'label' => array('Einstellungen', ''),
            'inputType' => 'group',
                'eval' => array('collapsible' => true, 'collapsed' => true),
        ),


        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
             'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true')
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


        'alternate_background_color' => array(
            'label' => array('Alternative Hintergrundfarbe für Inhalt', 'Standardmäßig transparent'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'alternate_text_color' => array(
            'label' => array('Schriftfarbe als HEX-Wert falls abweichend', 'Standard-Farbe ist die Basis-Textfarbe'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'activate_top' => array(
            'label' => array('Verlinkung der Box', ''),
            'inputType' => 'radio',
            'options' => array(
                '1' => 'Kein Beschreibungsfeld anzeigen',
                '2' => 'Beschreibungsbereich aktivieren',
            ),
            'default' => 1,
            'eval' => array('tl_class' => 'clr'),
        ),


        'swap_divs' => array(
            'label' => array('"Galerie" unterhalb von Beschreibungsfeld anzeigen', 'Standard: darüber'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'activate_top',
            ),
        ),


        'show_tooltip' => array(
            'label' => array('Tooltip anzeigen', 'Der sichtbare Bild-Titel wird ausgeblendet und es wird ein Tooltip nach Mouse-Over angezeigt'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'settings_2' => array(
            'label' => array('Standard-Inhalte', ''),
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'activate_top',
            ),
                'eval' => array('collapsible' => true, 'collapsed' => true),
        ),

        'basic_animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => array(
                /* Fading entrances  */
              'animate__fadeIn' => 'fadeIn (Standard)',
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
            'eval' => array('chosen' => 'true')
        ),


        'base_img' => array(
            'label' => array('Standard-Bild', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
            ),
        ),

        'basic_img_title' => array(
            'label' => array('Bild-Titel', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),


        'basic_topline' => array(
            'label' => array('Topline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),

        'basic_subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'basic_headline' => array(
            'label' => array('Standard-Überschrift', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'basic_headline_type' => array(
            'label' => array(
                'de' => array('Typ der Überschrift', ''),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'eval' => array('tl_class' => 'w50'),
        ),

        'basic_onlystyle' => array(
            'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'basic_desc' => array(
            'label' => array('Standard-Beschreibung', 'wird initial angezeigt und auch dann, wenn ein Element keinen Text hat.'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),


        'simple_elements' => array(
            'label' => array('Elemente', ''),
            'elementLabel' => '%s. Element',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(
                'img' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    ),
                ),

                'title' => array(
                    'label' => array('Titel', ''),
                    'inputType' => 'text',
                ),
            ),
            'dependsOn' => array(
                'field' => 'activate_top',
                'value' => '1'
            ),
        ),


        'elements' => array(
            'label' => array('Elemente', ''),
            'elementLabel' => '%s. Element',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(


                'alternate_topline' => array(
                    'label' => array('Topline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
                ),

                'alternate_subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
                ),

                'alternate_headline' => array(
                    'label' => array('Standard-Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'clr', 'allowHtml' => true),
                ),


                'img' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    ),
                ),

                'title' => array(
                    'label' => array('Bild-Titel', ''),
                    'inputType' => 'text',
                ),


                'desc' => array(
                    'label' => array('Alternative Beschreibung', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),
            ),


            'dependsOn' => array(
                'field' => 'activate_top',
                'value' => '2'
            ),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);