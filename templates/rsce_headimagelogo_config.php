<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

//rsce_my_element_config.php
return array(
    'label' => array('Custom | Kopfbild (oder Slider) mit Text (headimagelogo)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
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
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),

        'settings_size' => array(
            'label' => array('Größeneinstellung der Bilder', ''),
            'inputType' => 'group',
            'eval' => array('collapsible' => true, 'collapsed' => true),
        ),

        'not_as_bg' => array(
            'label' => array('Desktop-Bild "skalierbar" integrieren', 'Höhen-Einstellungen werden dadurch unwirksam'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'image_minheight' => array(
            'label' => array('Minimale Höhe des Bereichs (Desktop)', 'Einheit (px, rem, vh usw.) bitte angeben. Standard: 700px;'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'image_maxheight' => array(
            'label' => array('Maximale Höhe des Bereichs (Desktop)', 'Einheit (px, rem, vh usw.) bitte angeben. Standard: 100%;'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'not_as_bg_mobile' => array(
            'label' => array('Mobile-Bild "skalierbar" integrieren', 'Höhen-Einstellungen werden dadurch unwirksam'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'image_mobile_maxheight' => array(
            'label' => array('Maximale Höhe des Bereichs (Mobile)', 'Einheit (px, rem, vh usw.) bitte angeben'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'image_mobile_minheight' => array(
            'label' => array('Minimale Höhe des Bereichs (Mobile)', 'Einheit (px, rem, vh usw.) bitte angeben'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'settings_1' => array(
            'label' => array('Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('collapsible' => true, 'collapsed' => true),
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

        'show_breadcrumb' => array(
            'label' => array('Breadcrumb darunter anzeigen', ''),
            'inputType' => 'checkbox',
        ),

        'move_content' => array(
            'label' => array('Inhalt darunter hinter dem Slider verschwinden lassen', 'Funktioniert nur, wenn dass das erste Element im Artikel ist und darunter Elemente kommen und der Header "fixed" ist'),
            'inputType' => 'checkbox',
        ),

        'diagonal_cut' => array(
            'label' => array('Mit diagonalem Abschluss', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

             'boxed_image_check' => array(
            'label' => array('Boxed-Image (z. B. Logo) anzeigen', 'mittig unter dem Slider'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'pull_content' => array(
            'label' => array('Inhalt darunter "hochziehen"', ''),
            'inputType' => 'checkbox',
        ),

        'settings_slider' => array(
            'label' => array('Slider', ''),
            'inputType' => 'group',
            'eval' => array('collapsible' => true, 'collapsed' => true),
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

        'transition_time' => array(
            'label' => array('Animationszeit in ms', 'Standard: 1500'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'autoplay' => array(
            'label' => array('Autoplay aktivieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'autoplay_time' => array(
            'label' => array('Autoplay-Zyklus', 'nach wie viel MS soll zum nächsten Slide gewechselt werden, Standard: 3000'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'autoplay',
            ),
        ),


        'settings_2' => array(
            'label' => array('Boxed-Image (z. B. ein Logo)', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr', 'collapsible' => true, 'collapsed' => true),
                'dependsOn' => array(
                'field' => 'boxed_image_check',
            ),
        ),
        'boxed_image' => array(
            'label' => array('Bild mittig unten liegend', 'Meistens ein Logo'),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
            ),

        ),
        'boxed_image_animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation (Der Bild-Box)', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true')
        ),

        'boxed_image_size' => array(
            'label' => array('Bildbreite und Bildhöhe', ''),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',

                'includeBlankOption' => true,
            ),
        ),


        'slides' => array(
            'label' => array('Slides', ''),
            'elementLabel' => '%s. Slide',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 10,
            'fields' => array(

                'settings_3' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'group',
                ),


                'image' => array(
                    'label' => array('Bild / Video', 'Video-Format: MP4/WEBM'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,mp4,webm,webp',

                    ),
                ),


                'size' => array(
                    'label' => array('Bildbreite und Bildhöhe', ''),
                    'inputType' => 'imageSize',
                    'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',

                        'includeBlankOption' => true,
                    ),
                ),


                'mobile_image' => array(
                    'label' => array('Bild / Video für mobile-Ansicht', 'Nur in Verbindung mit einem Video in der Desktop-Ansicht, Video-Format: MP4'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,mp4,webp',

                        'mandatory' => false,
                    ),
                ),


                'size_mobile' => array(
                    'label' => array('Bildbreite und Bildhöhe (Mobile)', ''),
                    'inputType' => 'imageSize',
                    'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',

                        'includeBlankOption' => true,
                    ),
                ),


                'settings_4' => array(
                    'label' => array('Text-Inhalt', ''),
                    'inputType' => 'group',
                ),

                'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true')
                ),


                'text_style' => array(
                    'label' => array(
                        'de' => array('Text-Darstellungstyp', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'style-1' => 'Style 1: Überschrift in groß darstellen',
                        'style-2' => 'Style 2: Überschrift auf Hintergrund mit Diagonale legen',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),

                'textbox_position' => array(
                    'label' => array(
                        'de' => array('Textbox-Position', ''),
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

                    'dependsOn' => array(
                        'field' => 'text_style',
                        'value' => 'style-1',
                    ),
                ),


                'text_color' => array(
                    'label' => array('Schriftfarbe als HEX-Wert falls abweichend', 'Standard-Farbe ist die Basis-Textfarbe'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'headline_color' => array(
                    'label' => array('Headline-Schriftfarbe als HEX-Wert falls abweichend', 'Standard-Farbe ist die Basis-Textfarbe'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'alternate_background_color' => array(
                    'label' => array('Alternative Hintergrundfarbe für Inhalt', 'Standardmäßig transparent (Style 1) oder weiß (Style 2)'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'text_align' => array(
                    'label' => array(
                        'de' => array('Text-Ausrichtung', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'text-start' => 'Linksbündig',
                        'text-center' => 'Zentriert',
                        'text-end' => 'Rechtsbündig',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),

                'maxWidth' => array(
                    'label' => array('Maximale Breite der Textbox', 'inkl. Maßeinheit, z. B. 400px'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'clr'),
                ),

                /*
                      'dynamic_fontsize' => array(
                    'label' => array('Schriftgröße abhängig von Bildschirmbreite skalieren', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),

                 */


                'text_topline' => array(
                    'label' => array('Topline', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
                ),

                'text_secondline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
                ),

                'text_firstline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
                ),

                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', ''),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getHeadlineTagOptions(),
                    'eval' => array('tl_class' => 'w50'),
                ),


                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),

                'text_infotext' => array(
                    'label' => array('Langtext unterhalb der Überschriften', 'Nicht sichtbar auf Mobile!'),
                    'inputType' => 'textarea',
                    'eval' => array('tl_class' => 'clr', 'rte' => 'tinyMCE'),
                ),

                'own_box' => array(
                    'label' => array('Langtext in eigener Box unterhalb der Überschriften anzeigen', 'Nicht sichtbar auf Mobile!'),
                    'inputType' => 'checkbox',
                    'dependsOn' => array(
                        'field' => 'text_style',
                        'value' => 'style-1',
                    ),
                ),


                'box_background_color' => array(
                    'label' => array('Alternative Hintergrundfarbe für Inhalt', 'Standardmäßig transparent (Style 1) oder weiß (Style 2)'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'own_box',
                    ),
                ),


                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 20,
                    'eval' => array('tl_class' => 'clr'),
                    'fields' => ButtonHelper::getButtonConfig(),
                ),


            ),
        ),
    ),
);
