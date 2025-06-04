<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Contao\Config;
use Contao\System;

//rsce_my_element_config.php
return array(
    'label' => array('Custom | Überschrift & Text (headline)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'animation_type_element' => array(
            'label' => array('Animation: Komplettes Element', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'clr', 'submitOnChange' => true, 'includeBlankOption' => true),
        ),

        'header_image_options' => array(
            'label' => ['Kopfbild Optionen'],
            'inputType' => 'group',
        ),

        'add_header_image' => array(
            'label' => array('Kopfbild hinzufügen', 'Ein einzelnes Bild, das oberhalb des restlichen Inhalts angezeigt wird.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'header_image' => array(
            'label' => array('Kopfbild auswählen', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio', // Wichtig für einzelne Auswahl
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
                'tl_class' => 'clr'
            ),
            'dependsOn' => array(
                'field' => 'add_header_image',
            ),
        ),

        'header_image_size' => array(
            'label' => array('Bildgröße (Kopfbild)', ''),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'w50'
            ),
            'dependsOn' => array(
                'field' => 'add_header_image',
            ),
        ),

        'layout_section_start' => array(
            'label' => ['Layout Optionen'],
            'inputType' => 'group',
        ),

        'two_columns' => array(
            'label' => array('Inhalte zwei-spaltig darstellen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'columns_container_css_class' => array(
            'label' => array('Zusätzliche CSS-Klasse für den inneren Container', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'column_width_content' => array(
            'label' => array('Spaltenbreite: Textspalte', 'Die Headlinespalte nimmt den restlichen Platz ein.'),
            'inputType' => 'select',
            'options' => array(
                '16.67' => '16.67% (1/6)',
                '20' => '20% (1/5)',
                '25' => '25% (1/4)',
                '30' => '30%',
                '33.33' => '33.33% (1/3)',
                '40' => '40% (2/5)',
                '41.67' => '41.67% (5/12)',
                '50' => '50% (1/2)',
                '58.33' => '58.33% (7/12)',
                '60' => '60% (3/5)',
                '66.67' => '66.67% (2/3)',
                '70' => '70%',
                '75' => '75% (3/4)',
                '80' => '80% (4/5)',
                '83.33' => '83.33% (5/6)',
            ),
            'default' => '50',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array('field' => 'two_columns'),
        ),

        'headline_above_columns' => array(
            'label' => array('Headline oberhalb beider Spalten darstellen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'two_columns',
            ),
        ),

        'column_order' => array(
            'label' => array('Spaltenreihenfolge', 'Bestimmt, welche Spalte auf Mobil bzw. Desktop zuerst angezeigt wird.'),
            'inputType' => 'select',
            'options' => array(
                'hl_hl' => 'Standard (Headline immer zuerst)',
                'hl_co' => 'Desktop: Inhalt zuerst',
                'co_hl' => 'Mobil: Inhalt zuerst',
                'co_co' => 'Mobil & Desktop: Inhalt zuerst',
            ),
            'default' => 'hl_hl',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'two_columns',
            ),
        ),

        'add_second_content' => array(
            'label' => array('Zweites Inhaltsfeld integrieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'two_columns',
            ),
        ),

        'animation_type_headline' => array(
            'label' => array('Animation: Headline', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'animation_type_element',
                'value' => array('', 'no-animation'),
            ),
        ),

        'onlystyle' => array(
            'label' => array('Nur als Überschrift darstellen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'topline' => array(
            'label' => array('Topline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => ' clr', 'allowHtml' => true),
        ),

        'headline' => array(
            'label' => array('Überschrift', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),

        'headline_type' => array(
            'label' => array('Typ der Überschrift', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'eval' => array('tl_class' => 'w50'),
        ),

        'is_quote' => array(
            'label' => array('Ist Zitat', 'Stellt die Headline als Zitat dar (visuelle Anpassung).'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'clr', 'allowHtml' => true),

        ),

        'animation_type_headline_column' => array(
            'label' => array('Animation: Headlinespalte', 'Animiert die komplette Headlinespalte bei zweispaltiger Ansicht.'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'two_columns',
            ),
        ),

        'second_content' => array(
            'label' => array('Zusätzlicher Text (Headline-Spalte)', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'add_second_content',
            ),
        ),

        'headline_column_image_options' => array(
            'label' => ['Bildoptionen (Headline-Spalte)'],
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'add_second_content',
            ),
        ),
        'add_headline_column_image' => array(
            'label' => array('Bild in Headline-Spalte hinzufügen', 'Ein einzelnes Bild, das in der Headline-Spalte unter dem zweiten Inhalt angezeigt wird.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'add_second_content',
            ),
        ),
        'headline_column_image' => array(
            'label' => array('Bild für Headline-Spalte auswählen', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
                'tl_class' => 'clr'
            ),
            'dependsOn' => array(
                'field' => 'add_headline_column_image',
            ),
        ),
        'headline_column_image_size' => array(
            'label' => array('Bildgröße (Headline-Spalte)', ''),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'w50'
            ),
            'dependsOn' => array(
                'field' => 'add_headline_column_image',
            ),
        ),
        'headline_column_open_lightbox' => array(
            'label' => array('Bild in Lightbox öffnen (Headline-Spalte)', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'add_headline_column_image',
            ),
        ),

        'headline_column_buttons' => array(
            'label' => array('Buttons (Headline-Spalte)', ''),
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 20,
            'eval' => array('tl_class' => ' clr'),
            'fields' => ButtonHelper::getButtonConfig(false),
            'dependsOn' => array(
                'field' => 'add_second_content',
            ),
        ),

        'headline_column_button_alignment' => array(
            'label' => array('Textausrichtung der Buttons (Headline-Spalte)', ''),
            'inputType' => 'select',
            'options' => array(
                'text-lg-start' => 'Linksbündig (Standard)',
                'text-lg-center' => 'Zentriert',
                'text-lg-end' => 'Rechtsbündig',
            ),
            'default' => 'text-lg-start',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'add_second_content',
            ),
        ),

        'column_vertical_alignment' => array(
            'label' => array('Vertikale Ausrichtung der Spalten', 'Gilt nur bei zweispaltiger Ansicht.'),
            'inputType' => 'select',
            'options' => array(
                '' => 'Oben (Standard)',
                'align-items-center' => 'Mittig',
                'align-items-end' => 'Unten',
            ),
            'eval' => array('tl_class' => ' clr'),
            'dependsOn' => array(
                'field' => 'two_columns',
            ),
        ),

        'headline_column_css_class' => array(
            'label' => array('Zusätzliche CSS-Klasse für Headlinespalte', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'two_columns',
            ),
        ),

        'content_column_css_class' => array(
            'label' => array('Zusätzliche CSS-Klasse für Inhaltsspalte', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 '),
            'dependsOn' => array(
                'field' => 'two_columns',
            ),
        ),

        'content_section_start' => array(
            'label' => ['Inhalts Optionen'],
            'inputType' => 'group',
        ),

        'desc' => array(
            'label' => array('Text (Textspalte)', 'optional'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),

        'animation_type_content' => array(
            'label' => array('Animation: Textspalte', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'animation_type_element',
                'value' => array('', 'no-animation'),
            ),
        ),


        'add_images' => array(
            'label' => array('Bilder hinzufügen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'image_options_group' => array(
            'label' => ['Bild Optionen (Textspalte)'],
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'add_images',
            ),
        ),

        'deactivate_slider' => array(
            'label' => array('Bildanzeige Modus', 'Wie sollen die Bilder dargestellt werden?'),
            'inputType' => 'select',
            'options' => array(
                '' => 'Slider (Standard)',
                'desktop' => 'Liste auf Desktop / Slider auf Mobile',
                'mobile' => 'Slider auf Desktop / Liste auf Mobile',
                'both' => 'Immer Liste',
            ),
            'eval' => array('tl_class' => ' clr'),

        ),

        'headline_image_size' => array(
            'label' => array('Bildgröße', 'Gilt für die hinzugefügten Bilder.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'clr'
            ),

        ),

        'open_lightbox' => array(
            'label' => array('Bilder in Lightbox öffnen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),

        ),

        'multiSRC' => [
            'label' => ['Bilder', ''],
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => true,
                'fieldType' => 'checkbox',
                'orderField' => 'orderSRC',
                'files' => true,
                'mandatory' => false,
                'isGallery' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
            ),

        ],

        'image_spacing_class' => array(
            'label' => array('Abstandsklasse für Bilder (Listen-Ansicht)', 'Standard: mb-1. Wird beim letzten Bild nicht angewendet.'),
            'inputType' => 'text',
            'default' => 'mb-1',
            'eval' => array('tl_class' => 'clr'),
            /*
            'dependsOn' => array(
                'field' => 'deactivate_slider',
                'value' => array('desktop', 'mobile', 'both'),
            ),
            */
        ),

        'slide_animation_type' => array(
            'label' => array('Art der Einblendeanimation der Bilder', 'Siehe https://animate.style/'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'add_images',
            )
        ),

        'slider_options_group' => array(
            'label' => ['Slider Optionen'],
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'add_images',
            ),
            'eval' => array('tl_class' => 'clr'),
        ),

        'space_between' => array(
            'label' => array('Abstand zwischen den Slides in PX', 'Standard: 30'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'slides_per_view' => array(
            'label' => array('Wie viele Slides sind sichtbar', 'Beispielsweise 1.5 um rechts und links eine Vorschau des nächsten Slides anzuzeigen'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'slides_per_view_mobile' => array(
            'label' => array('Wie viele Slides sind sichtbar (mobile)', 'Standard: 1'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'slide_effect' => array(
            'label' => array('Slide-Effekt', ''),
            'inputType' => 'select',
            'options' => array('slide' => 'Slide (Standard)', 'coverflow' => 'Coverflow', 'fade' => 'Fade', 'flip' => 'Flip', 'cube' => 'Cube'),
            'eval' => array('tl_class' => 'w50'),
        ),
        'transition_time' => array(
            'label' => array('Animationszeit in ms', 'Standard: 1500'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'show_pagination' => array(
            'label' => array('Paginierung anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'show_arrows' => array(
            'label' => array('Pfeile anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'loop' => array(
            'label' => array('Loop', 'Automatisch wieder von Anfang starten'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'autoplay' => array(
            'label' => array('Autoplay aktivieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'autoplay_time' => array(
            'label' => array('Autoplay-Zyklus in ms', 'Standard: 3000'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array('field' => 'autoplay'),
        ),

        'buttons' => array(
            'label' => array('Buttons (Textspalte)', ''),
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 20,
            'eval' => array('tl_class' => ' clr'),
            'fields' => ButtonHelper::getButtonConfig(false),
        ),

        'button_group_text_alignment' => array(
            'label' => array('Textausrichtung der Buttons', 'Gilt für die gesamte Button-Gruppe in der Textspalte.'),
            'inputType' => 'select',
            'options' => array(
                'text-lg-start' => 'Linksbündig',
                'text-lg-center' => 'Zentriert',
                'text-lg-end' => 'Rechtsbündig (Standard)',
            ),
            'default' => 'text-lg-end',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

    ),
);
