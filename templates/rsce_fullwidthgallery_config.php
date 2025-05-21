<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Contao\StringUtil;

//rsce_my_element_config.php
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
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),

        'show_icon' => array(
            'label' => array('Info-Icon oben rechts anzeigen, falls Hover-Inhalte vorhanden sind', 'Um zu symbolisieren, dass hier nach Hover Inhalte existieren'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'gallery_layout' => array(
            'label' => array('Galerie-Layout', ''),
            'inputType' => 'select',
            'options' => array(
                'flex' => 'Flexbox Layout (Standard)',
            ),
            'eval' => array('tl_class' => 'w50'),
        ),

        'layout_settings' => array(
            'label' => array('Layout-Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
        ),

        'columns_xl' => array(
            'label' => array('Spalten pro Zeile (XL)', 'Ab 1200px Bildschirmbreite'),
            'inputType' => 'select',
            'options' => array(1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9', 10 => '10'),
            'default' => 4,
            'eval' => array('tl_class' => 'w25'),
        ),

        'columns_desktop' => array(
            'label' => array('Spalten pro Zeile (Desktop)', 'Ab 992px Bildschirmbreite'),
            'inputType' => 'select',
            'options' => array(1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9', 10 => '10'),
            'default' => 3,
            'eval' => array('tl_class' => 'w25'),
        ),

        'columns_tablet' => array(
            'label' => array('Spalten pro Zeile (Tablet)', 'Zwischen 768px und 992px Bildschirmbreite'),
            'inputType' => 'select',
            'options' => array(1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9', 10 => '10'),
            'default' => 2,
            'eval' => array('tl_class' => 'w25'),
        ),

        'columns_mobile' => array(
            'label' => array('Spalten pro Zeile (Mobile)', 'Unter 768px Bildschirmbreite'),
            'inputType' => 'select',
            'options' => array(1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9', 10 => '10'),
            'default' => 1,
            'eval' => array('tl_class' => 'w25'),
        ),

        'gap' => array(
            'label' => array('Abstand zwischen den Elementen (px)', 'Beispiel: 10'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
            'default' => 0,
        ),

        'min_height' => array(
            'label' => array('Mindesthöhe der Elemente (px)', 'Optional: Leer lassen für automatische Höhe'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
        ),

        'selecttype' => array(
            'label' => array('Bilder ', ''),
            'inputType' => 'radio',
            'eval' => array('tl_class' => 'clr'),
            'options' => array(
                'multiple' => 'Mehrere Bilder oder Ordner auswählen',
                'single' => 'Bilder einzeln auswählen und optional Bildbeschreibung und Bildtitel hinzufügen',
            ),
        ),

        'multiSRC' => array(
            'inputType' => 'standardField',
            'eval' => array(
                'multiple' => true,
                'fieldType' => 'checkbox',
                'orderField' => 'orderSRC',
                'files' => true,
                'mandatory' => false,
                'isGallery' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
            ),
            'dependsOn' => array(
                'field' => 'selecttype',
                'value' => 'multiple',
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

        'open_lightbox' => array(
            'label' => array('Bilder in Lightbox öffnen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
            'dependsOn' => array(
                'field' => 'selecttype',
                'value' => 'multiple',
            ),
        ),

        'custom_column_widths' => array(
            'label' => array('Spaltenbreite für jedes Bild individuell festlegen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'selecttype',
                'value' => 'single',
            ),
        ),

        'gallery' => array(
            'label' => array('Elemente', ''),
            'elementLabel' => '%s. Element',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'dependsOn' => array(
                'field' => 'selecttype',
                'value' => 'single',
            ),
            'fields' => array(

                'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
                ),

                'column_width' => array(
                    'label' => array('Relativer Platzbedarf (Gewichtung)', 'Nur wirksam, wenn "Spaltenbreite für jedes Bild individuell festlegen" aktiv ist. Bestimmt das relative Gewicht des Elements in der Flexbox-Zeile (Standard: 1).'),
                    'inputType' => 'select',
                    'options' => array(
                        '' => 'Standard (Gewicht 1)',
                        '1' => 'Gewicht 1',
                        '2' => 'Gewicht 2',
                        '3' => 'Gewicht 3',
                        '4' => 'Gewicht 4',
                        '5' => 'Gewicht 5',
                        '6' => 'Gewicht 6',
                        '7' => 'Gewicht 7',
                        '8' => 'Gewicht 8',
                        '9' => 'Gewicht 9',
                        '10' => 'Gewicht 10',
                    ),
                    'default' => '',
                    'eval' => array(
                        'tl_class' => 'w50',
                        'includeBlankOption' => false
                    ),
                    'dependsOn' => array(
                        'field' => 'custom_column_widths',
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


                'show_initial_content' => array(
                    'label' => array('Inhalt initial auf Bild anzeigen', ''),
                    'inputType' => 'checkbox',
                    'options' => array(
                        '1' => 'Inhalt initial auf Bild anzeigen',
                    ),
                ),

                'settings_url' => array(
                    'label' => array('Box-Einstellungen', ''),
                    'inputType' => 'group',
                    'eval' => array('collapsible' => true, 'collapsed' => true),
                ),

                'box_link_type' => array(
                    'label' => array('Verlinkung der Box', ''),
                    'inputType' => 'radio',
                    'options' => array(
                        '1' => 'Ganze Box verlinken',
                        '2' => 'Buttons anzeigen',
                        '3' => 'Bild in Großansicht anzeigen',
                    ),
                    'default' => 1
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
                    'options' => GlobalElementConfig::getHeadlineTagOptions(),
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
                    'options' => GlobalElementConfig::getAnimations(),
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
                    'options' => GlobalElementConfig::getHeadlineTagOptions(),
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
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 20,
                    'eval' => array('tl_class' => 'clr'),
                    'fields' => ButtonHelper::getButtonConfig(),
                    'dependsOn' => array(
                        'field' => 'box_link_type',
                        'value' => '2',
                    ),
                ),
            ),
        ),
    ),
);