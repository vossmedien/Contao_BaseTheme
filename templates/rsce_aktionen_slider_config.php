<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

return array(
    'label' => array('Custom | Aktionen Slider', 'Slider zur Anzeige von Aktionen mit konfigurierbaren Boxen'),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        // Headline settings
        'headline_settings' => array(
            'label' => array('Überschrift-Einstellungen', ''),
            'inputType' => 'group',
        ),
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
            'label' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
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


        // Slider settings
        'slider_settings' => array(
            'label' => array('Slider-Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
        ),
        'slide_animation_type' => array(
            'label' => array('Art der Einblendeanimation der Slides', 'Siehe https://animate.style/ für Beispiele'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
        ),
        'space_between' => array(
            'label' => array('Abstand zwischen den Slides in PX', 'Standard: 30'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'slides_per_view' => array(
            'label' => array('Wie viele Slides sind sichtbar (Desktop)', 'Standard: 2'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'slides_per_view_tablet' => array(
            'label' => array('Wie viele Slides sind sichtbar (Tablet)', 'Standard: 2'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'slides_per_view_mobile' => array(
            'label' => array('Wie viele Slides sind sichtbar (Mobile)', 'Standard: 1'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'slide_effect' => array(
            'label' => array('Slide-Effekt', ''),
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

        // Control settings
        'control_settings' => array(
            'label' => array('Steuerungs-Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
        ),
        'show_pagination' => array(
            'label' => array('Paginierung anzeigen', 'mittig unter dem Slider, in Form von Punkten'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'show_arrows' => array(
            'label' => array('Pfeile anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'centered_slides' => array(
            'label' => array('Slides mittig ausrichten', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'loop' => array(
            'label' => array('Automatisch wieder von Anfang starten', '"loop"'),
            'inputType' => 'checkbox',
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


        'actions' => array(
            'label' => array('Aktionsboxen', ''),
            'elementLabel' => '%s. Aktion',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 99,
            'eval' => array('doNotCopy' => false),
            'fields' => array(

                'animation_type' => array(
                    'label' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('tl_class' => 'w50'),
                ),
                'box_color' => array(
                    'label' => array('Boxfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'mandatory' => true),
                ),
                'title_text_color' => array(
                    'label' => array('Titeltext-Farbe', 'In HEX oder rgb(a) angeben (Standard: weiß)'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'badge_text_color' => array(
                    'label' => array('Badge-Text-Farbe', 'In HEX oder rgb(a) angeben (Standard: weiß)'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                // Image settings
                'image_settings' => array(
                    'label' => array('Bild-Einstellungen', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'image' => array(
                    'label' => array('Aktionsbild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,webp',
                        'mandatory' => true,
                    ),
                ),
                'add_link' => array(
                    'label' => array('Link zum Bild hinzufügen', 'z.B. für einen Download'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'image_link_url' => array(
                    'label' => array('Link-URL', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'rgxp'=>'url', 'decodeEntities'=>true, 'dcaPicker'=>true, 'addWizardClass'=>false),
                    'dependsOn' => array(
                        'field' => 'add_link',
                    ),
                ),
                'image_link_title' => array(
                    'label' => array('Link-Titel', 'Wird als Title-Attribut verwendet'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'add_link',
                    ),
                ),
                'image_link_target' => array(
                    'label' => array('Link in neuem Tab öffnen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'add_link',
                    ),
                ),

                // Title settings
                'title_content' => array(
                    'label' => array('Titel-Einstellungen', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'title' => array(
                    'label' => array('Aktionstitel', ''),
                    'inputType' => 'textarea',
                    'eval' => array('tl_class' => 'clr', 'allowHtml' => true, 'rte' => 'tinyMCE', 'mandatory' => true, 'rows' => 3),
                ),

                // Partner URL settings
                'partner_settings' => array(
                    'label' => array('Partner-URL-Einstellungen', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'partner_url' => array(
                    'label' => array('Partner-URL', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'rgxp'=>'url', 'decodeEntities'=>true, 'dcaPicker'=>true, 'addWizardClass'=>false),
                ),
                'partner_url_text' => array(
                    'label' => array('Partner-URL-Text', 'Falls abweichend von der URL'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'partner_url_target' => array(
                    'label' => array('Partner-Link in neuem Tab öffnen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                ),

                // Badge settings
                'badge_settings' => array(
                    'label' => array('Badge-Einstellungen', ''),
                    'inputType' => 'group',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'badge_type' => array(
                    'label' => array('Badge-Typ', ''),
                    'inputType' => 'select',
                    'options' => array(
                        'circle' => 'Kreis (Standard)',
                        'static' => 'Statischer Box',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),
                'badge_content' => array(
                    'label' => array('Badge-Inhalt', 'HTML ist erlaubt'),
                    'inputType' => 'textarea',
                    'eval' => array('tl_class' => 'clr', 'allowHtml' => true, 'rte' => 'tinyMCE', 'rows' => 5),
                ),
                'badge_link_to_file' => array(
                    'label' => array('Badge-Inhalt verlinken', 'Gleiche Verlinkung wie bei Bild verwenden'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'add_link',
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