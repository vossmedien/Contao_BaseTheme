<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

//rsce_my_element_config.php
return array(
    'label' => array('Custom | Fixed Störer (stoerer)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(


        'is_fixed' => array(
            'label' => array('Störer scrollt mit', ''),
            'inputType' => 'checkbox',
        ),

        'integrate_footer' => array(
            'label' => array('Footer integrieren', 'Zusätzliche Bildelemente unterhalb des Hauptinhalts anzeigen'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'size' => array(
            'label' => array('Bildgröße (Hauptbild)', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
            ),
        ),

        'size_mobile' => array(
            'label' => array('Bildgröße (mobile)', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
            ),
        ),

        'settings_position' => array(
            'label' => array('Positionierung', ''),
            'inputType' => 'group',
        ),


        /*
               'text_rotation' => array(
            'label' => array('Gradzahl der Drehung', 'Falls der Störer im Uhrzeigersinn geneigt werden soll'),
            'inputType' => 'text',
            'eval' => array('tl_class' => ''),
        ),
         */


        'alternate_top_position' => array(
            'label' => array('Abstand von oberer Bildschirmkante', 'Standard: 150px - entweder OBEN oder UNTEN ausfüllen'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'alternate_bottom_position' => array(
            'label' => array('Abstand von unterer Bildschirmkante', 'entweder OBEN oder UNTEN ausfüllen'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'alternate_right_position' => array(
            'label' => array('Abstand von rechter Bildschirmkante', 'Standard: 25px - entweder RECHTS oder LINKS ausfüllen'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'alternate_left_position' => array(
            'label' => array('Abstand von linker Bildschirmkante', 'entweder RECHTS oder LINKS ausfüllen'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'expand' => array(
            'label' => array('Inhalt nach Hover ausklappen', 'Standardmäßig immer sichtbar'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'stoerer' => array(
            'label' => array('Haupt-Elemente', ''),
            'elementLabel' => '%s. Haupt-Element',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(
                'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('tl_class' => 'clr')
                ),

                'settings_color_trigger' => array(
                    'label' => array('Farben (Trigger)', 'Farben für das sichtbare Icon/Bild'),
                    'inputType' => 'group',
                    'eval' => array('collapsible' => true, 'collapsed' => true, 'tl_class' => 'w50'),
                ),

                'trigger_text_color' => array(
                    'label' => array('Schriftfarbe Trigger', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'trigger_text_hover_color' => array(
                    'label' => array('Schriftfarbe Trigger (Hover)', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'trigger_bg_color' => array(
                    'label' => array('Hintergrundfarbe Trigger', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'trigger_bg_hover_color' => array(
                    'label' => array('Hintergrundfarbe Trigger (Hover)', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'settings_color_content' => array(
                    'label' => array('Farben (Inhalt)', 'Farben für den ausklappenden Inhaltsbereich'),
                    'inputType' => 'group',
                    'eval' => array('collapsible' => true, 'collapsed' => true, 'tl_class' => 'w50'),
                ),

                'content_text_color' => array(
                    'label' => array('Schriftfarbe Inhalt', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'content_text_hover_color' => array(
                    'label' => array('Schriftfarbe Inhalt (Hover)', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'content_bg_color' => array(
                    'label' => array('Hintergrundfarbe Inhalt', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'content_bg_hover_color' => array(
                    'label' => array('Hintergrundfarbe Inhalt (Hover)', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'settings_inhalt' => array(
                    'label' => array('Inhalt', ''),
                    'inputType' => 'group',
                ),

                'content_type' => array(
                    'label' => array('Art des Inhalts', 'Wählen Sie, ob Text oder Buttons angezeigt werden sollen.'),
                    'inputType' => 'select',
                    'options' => array(
                        'text' => 'Text anzeigen',
                        'buttons' => 'Buttons mit Label untereinander anzeigen',
                    ),
                    'default' => 'text',
                    'eval' => array('tl_class' => ' clr'),
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

                'img_mobile' => array(
                    'label' => array('Bild (mobile)', 'Alternativbild für mobile Geräte'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'tl_class' => ' clr',
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    ),
                ),
                'content' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                    'dependsOn' => array(
                        'field' => 'content_type',
                        'value' => 'text',
                    ),
                ),

                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 10,
                    'dependsOn' => array(
                        'field' => 'content_type',
                        'value' => 'buttons',
                    ),
                    'fields' => array(
                        'button_label' => array(
                            'label' => array('Button Label', 'Text oberhalb des Buttons'),
                            'inputType' => 'text',
                            'eval' => array('tl_class' => 'w50 '),
                        ),
                        'animation_type' => array(
                            'label' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                            'inputType' => 'select',
                            'options' => GlobalElementConfig::getAnimations(),
                               'eval' => array('tl_class' => 'w50 '),
                        ),
                        'link_text' => array(
                            'label' => array('Link-Beschriftung (Button-Text)', ''),
                            'inputType' => 'text',
                            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
                        ),
                        'link_id' => array(
                            'label' => array('Button-ID', 'Beispielsweise für Analytics-Events'),
                            'inputType' => 'text',
                            'eval' => array('tl_class' => 'w50'),
                        ),
                        'link_url' => array(
                            'label' => array('Verlinkung', ''),
                            'inputType' => 'url',
                            'eval' => array('tl_class' => 'w50'),
                        ),
                        'link_betreff' => array(
                            'label' => array('Betreffzeile für "mailto:"-Buttons', '(optional)'),
                            'inputType' => 'text',
                            'eval' => array('tl_class' => 'w50'),
                        ),
                        'new_tab' => array(
                            'label' => array('Link in neuen Tab öffnen', ''),
                            'inputType' => 'checkbox',
                            'eval' => array('tl_class' => 'clr '),
                        ),
                        'link_type' => array(
                            'label' => array('Optik des Buttons', ''),
                            'inputType' => 'select',
                            'options' => [
                                // Hauptfarbe-Buttons
                                'btn-primary' => 'Hauptfarbe',
                                'btn-primary with-arrow' => 'Hauptfarbe mit Pfeil',
                                'btn-outline-primary' => 'Hauptfarbe (Outline)',
                                'btn-outline-primary with-arrow' => 'Hauptfarbe (Outline) mit Pfeil',

                                // Sekundär-Buttons
                                'btn-secondary' => 'Sekundär-Farbe',
                                'btn-secondary with-arrow' => 'Sekundär-Farbe mit Pfeil',
                                'btn-outline-secondary' => 'Sekundär-Farbe (Outline)',
                                'btn-outline-secondary with-arrow' => 'Sekundär-Farbe (Outline) mit Pfeil',

                                // Tertiär-Buttons
                                'btn-tertiary' => 'Tertiär-Farbe',
                                'btn-tertiary with-arrow' => 'Tertiär-Farbe mit Pfeil',
                                'btn-outline-tertiary' => 'Tertiär-Farbe (Outline)',
                                'btn-outline-tertiary with-arrow' => 'Tertiär-Farbe (Outline) mit Pfeil',

                                // CurrentColor-Buttons
                                'btn-outline-currentColor' => 'Farbübernahme vom Elternelement (Outline)',
                                'btn-outline-currentColor with-arrow' => 'Farbübernahme vom Elternelement (Outline) mit Pfeil',

                                // Weiße Buttons
                                'btn-white' => 'Weißer Button mit schwarzer Schrift',
                                'btn-white with-arrow' => 'Weißer Button mit schwarzer Schrift und Pfeil',
                                'btn-outline-white' => 'Transparenter Button mit weißer Schrift und Rahmen',
                                'btn-outline-white with-arrow' => 'Transparenter Button mit weißer Schrift und Rahmen sowie Pfeil',

                                // Schwarze Buttons
                                'btn-black' => 'Schwarzer Button mit weißer Schrift',
                                'btn-black with-arrow' => 'Schwarzer Button mit weißer Schrift und Pfeil',
                                'btn-outline-black' => 'Transparenter Button mit schwarzer Schrift und Rahmen',
                                'btn-outline-black with-arrow' => 'Transparenter Button mit schwarzer Schrift und Rahmen sowie Pfeil',

                                // Rote/Danger Buttons
                                'btn-danger' => 'Roter Button',
                                'btn-danger with-arrow' => 'Roter Button mit Pfeil',
                                'btn-outline-danger' => 'Roter Button (Outline)',
                                'btn-outline-danger with-arrow' => 'Roter Button (Outline) mit Pfeil',

                                // Link-Buttons
                                'btn-link' => 'Link-Optik',
                                'btn-link with-arrow' => 'Link-Optik mit Pfeilen',
                            ],
                            'eval' => array('tl_class' => 'clr'),
                        ),
                        'link_size' => array(
                            'label' => array('Größe des Buttons', ''),
                            'inputType' => 'select',
                            'options' => [
                                '' => 'Standard',
                                'btn-sm' => 'Klein',
                                'btn-lg' => 'Groß',
                                'btn-xl' => 'Sehr groß',
                            ],
                            'eval' => array('tl_class' => 'clr'),
                        ),
                    ),
                ),
            ),
        ),

        'footer_elements' => array(
            'label' => array('Footer-Elemente', 'Werden unterhalb des Hauptinhalts angezeigt.'),
            'elementLabel' => '%s. Footer-Element',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 10,
            'dependsOn' => array(
                'field' => 'integrate_footer',
                'value' => '1',
            ),
            'fields' => array(
                'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('tl_class' => 'w50')
                ),
                'title' => array(
                    'label' => array('Tooltip-Titel', 'Text, der als Tooltip beim Hovern angezeigt wird.'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'img' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                        'tl_class' => 'clr'
                    ),
                ),
                'link_url' => array(
                    'label' => array('Verlinkung', ''),
                    'inputType' => 'url',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'new_tab' => array(
                    'label' => array('Link in neuen Tab öffnen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50 m12'),
                ),
            ),
        ),

        'footer_size' => array(
            'label' => array('Bildgröße (Footer-Elemente)', 'Zentrale Bildgröße für alle Elemente im Footer.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'dependsOn' => array(
                'field' => 'integrate_footer',
                'value' => '1',
            ),
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'clr w50'
            ),
        ),

        'settings_color_footer' => array(
            'label' => array('Farben (Footer)', 'Farben für den Footer-Bereich unter dem Inhalt'),
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'integrate_footer',
                'value' => '1',
            ),
            'eval' => array('collapsible' => true, 'collapsed' => true, 'tl_class' => 'clr'),
        ),

        'footer_bg_color' => array(
            'label' => array('Hintergrundfarbe Footer', ''),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'integrate_footer',
                'value' => '1',
            ),
            'eval' => array('tl_class' => 'w50 '),
        ),

        'footer_initial_color' => array(
            'label' => array('Initiale Farbe Schrift/SVG Footer', ''),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'integrate_footer',
                'value' => '1',
            ),
            'eval' => array('tl_class' => 'w50 '),
        ),

        'footer_bg_hover_color' => array(
            'label' => array('Hover-Hintergrundfarbe Footer', ''),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'integrate_footer',
                'value' => '1',
            ),
            'eval' => array('tl_class' => 'w50'),
        ),

        'footer_svg_hover_color' => array(
            'label' => array('Hover-Farbe Schrift/SVG Footer', ''),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'integrate_footer',
                'value' => '1',
            ),
            'eval' => array('tl_class' => 'w50'),
        ),

    ),
);
