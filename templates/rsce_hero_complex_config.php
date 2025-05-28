<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

return array(
    'label' => array('Custom | Hero Komplex', 'Ein komplexes Hero-Element mit Headline, Text, Bild/Video und einem Box-Slider.'),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'animations_group' => array(
            'label' => array('Animationen der Bereiche', ''),
            'inputType' => 'group',
        ),
        'animation_type_headline' => array(
            'label' => array('Animation: Linke Headline', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50 clr', 'includeBlankOption' => true),
        ),
        'animation_type_text' => array(
            'label' => array('Animation: Rechter Textblock', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50', 'includeBlankOption' => true),
        ),
        'animation_type_media' => array(
            'label' => array('Animation: Haupt-Bild/Video Bereich', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50 clr', 'includeBlankOption' => true),
        ),
        'animation_type_box' => array(
            'label' => array('Animation: Box-Slider Bereich', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50', 'includeBlankOption' => true),
            'dependsOn' => array(
                'field' => 'activate_box_slider',
            ),
        ),

        'layout_settings_group' => array(
            'label' => array('Layout Einstellungen', ''),
            'inputType' => 'group',
        ),
        'container_class' => array(
            'label' => array('CSS-Klasse für den Hauptcontainer', 'Standard: container-wide. Kann z.B. auf container geändert werden.'),
            'inputType' => 'text',
            'default' => 'container-wide',
            'eval' => array('tl_class' => 'w50'),
        ),

        // Haupt-Layout (oben)
        'main_layout_group' => array(
            'label' => array('Haupt-Layout (Oben)', ''),
            'inputType' => 'group',
        ),
        'main_headline' => array(
            'label' => array('Linke Headline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'mandatory' => true, 'allowHtml' => true),
        ),
        'main_headline_type' => array(
            'label' => array('Typ der linken Headline', ''),
            'inputType' => 'select',
            'default' => 'h1',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'eval' => array('tl_class' => 'w50'),
        ),
        'main_text' => array(
            'label' => array('Rechter Textblock', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr', 'allowHtml' => true),
        ),

        // Haupt-Bild/Video Bereich
        'main_media_group' => array(
            'label' => array('Haupt-Bild/Video Bereich (Desktop)', ''),
            'inputType' => 'group',
        ),
        'media_type' => array(
            'label' => array('Medientyp', 'Wählen Sie, ob ein einzelnes Bild oder ein einzelnes Video angezeigt werden soll.'),
            'inputType' => 'select',
            'options' => array(
                'image' => 'Einzelnes Bild',
                'video' => 'Einzelnes Video',
            ),
            'default' => 'image',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'main_image' => array(
            'label' => array('Hauptbild auswählen', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => Contao\Config::get('validImageTypes'),
                'tl_class' => 'clr',
                'mandatory' => true,
            ),
            'dependsOn' => array(
                'field' => 'media_type',
                'value' => 'image',
            ),
        ),
        'main_image_size' => array(
            'label' => array('Bildgröße Hauptbild', ''),
            'inputType' => 'imageSize',
            'options' => Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'w50',
            ),
            'dependsOn' => array(
                'field' => 'media_type',
                'value' => 'image',
            ),
        ),
        'main_video' => array(
            'label' => array('Hauptvideo auswählen', 'MP4 oder WEBM'),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'mp4,webm',
                'tl_class' => 'clr',
                'mandatory' => true,
            ),
            'dependsOn' => array(
                'field' => 'media_type',
                'value' => 'video',
            ),
        ),
        'image_minheight' => array(
            'label' => array('Minimale Höhe des Medienbereichs (Desktop)', 'Einheit (px, rem, vh usw.) bitte angeben. Standard: 700px;'),
            'inputType' => 'text',
            'default' => '700px',
            'eval' => array('tl_class' => 'w50 clr'),
        ),



        // Mobile Medien Gruppe als Fieldset
        'mobile_media_group' => array(
            'label' => array('Mobile Bild/Video Einstellungen', ''),
            'inputType' => 'group',

        ),
        'mobile_media_type' => array(
            'label' => array('Mobiler Medientyp (optional)', 'Wählen Sie, ob ein Bild oder Video für mobile Ansichten verwendet werden soll.'),
            'inputType' => 'select',
            'options' => array(
                'image' => 'Mobiles Bild',
                'video' => 'Mobiles Video',
            ),
            'default' => 'image',
            'eval' => array('tl_class' => 'w50 clr'),

        ),
        'mobile_image' => array(
            'label' => array('Mobiles Bild auswählen', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => Contao\Config::get('validImageTypes'),
                'tl_class' => 'clr',
            ),
            'dependsOn' => array(
             'field' => 'mobile_media_type', 'value' => 'image'
            ),
        ),
        'mobile_image_size' => array(
            'label' => array('Bildgröße mobiles Bild', ''),
            'inputType' => 'imageSize',
            'options' => Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'w50',
            ),
            'dependsOn' => array(
       'field' => 'mobile_media_type', 'value' => 'image'
            ),
        ),
        'mobile_video' => array(
            'label' => array('Mobiles Video auswählen', 'MP4 oder WEBM'),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'mp4,webm',
                'tl_class' => 'clr',
            ),
            'dependsOn' => array(
               'field' => 'mobile_media_type', 'value' => 'video',
            ),
        ),
        'image_mobile_minheight' => array(
            'label' => array('Minimale Höhe des Medienbereichs (Mobile)', 'Einheit (px, rem, vh usw.) bitte angeben. Standard: auto;'),
            'inputType' => 'text',
            'default' => 'auto',
            'eval' => array('tl_class' => 'w50'),
        ),

        // Box-Slider Bereich
        'box_slider_group' => array(
            'label' => array('Box-Slider Einstellungen', ''),
            'inputType' => 'group',
        ),
        'activate_box_slider' => array(
            'label' => array('Box-Slider aktivieren', 'Zeigt eine Box mit einem eigenen Slider an.'),
            'inputType' => 'checkbox',
            'eval' => array( 'tl_class' => 'clr'),
        ),
        'box_background_color' => array(
            'label' => array('Hintergrundfarbe der Box', 'z.B. #FFFFFF oder rgba(0,0,0,0.5)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'activate_box_slider',
            ),
        ),
        'box_font_color' => array(
            'label' => array('Schriftfarbe in der Box', 'z.B. #000000'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'activate_box_slider',
            ),
        ),
        'box_slides' => array(
            'label' => array('Slides für die Box', ''),
            'elementLabel' => '%s. Slide in der Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 10,
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'activate_box_slider',
            ),
            'fields' => array(
                'slide_headline' => array(
                    'label' => array('Überschrift des Slides', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
                ),
                'slide_headline_type' => array(
                    'label' => array('Typ der Slide-Überschrift', ''),
                    'inputType' => 'select',
                    'default' => 'h3',
                    'options' => GlobalElementConfig::getHeadlineTagOptions(),
                    'eval' => array('tl_class' => 'w50'),
                ),
                'slide_content' => array(
                    'label' => array('Inhaltstext des Slides', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr', 'allowHtml' => true),
                ),
                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 3,
                    'eval' => array('tl_class' => 'clr'),
                    'fields' => ButtonHelper::getButtonConfig(),
                ),
            ),
        ),
        'box_slider_autoplay' => array(
            'label' => array('Autoplay für Box-Slider aktivieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array('field' => 'activate_box_slider'),
        ),
        'box_slider_autoplay_time' => array(
            'label' => array('Autoplay-Zyklus Box-Slider (ms)', 'Standard: 5000'),
            'inputType' => 'text',
            'default' => '5000',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'digit'),
            'dependsOn' => array('field' => 'box_slider_autoplay'),
        ),
         'box_slider_loop' => array(
            'label' => array('Loop für Box-Slider aktivieren', ''),
            'inputType' => 'checkbox',
            'default' => true,
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array('field' => 'activate_box_slider'),
        ),
        'box_slider_transition_time' => array(
            'label' => array('Übergangszeit Box-Slider (ms)', 'Standard: 1000'),
            'inputType' => 'text',
            'default' => '1000',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'digit'),
            'dependsOn' => array('field' => 'activate_box_slider'),
        ),
    ),
);