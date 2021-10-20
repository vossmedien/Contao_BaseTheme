<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Boxen mit Bild, Text & Verlinkung', ''),
    'types' => array('content'),
    'contentCategory' => 'texts',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
        ),


        'box_style' => array(
            'label' => array(
                'de' => array('Darstellungstyp der Boxen', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'style-1' => 'Abgerundete Ecken, Schatten, zentriert',
                'style-2' => 'Grauer Hintergrund, linksbündig, Pfeil auf rechter Seite',
            ),
        ),

        'boxes' => array(
            'label' => array('Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 99,
            'fields' => array(
                'column_width' => array(
                    'label' => array(
                        'de' => array('Spaltenbreite', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12 col-md-6 col-lg-3' => '25%',
                        'col-12 col-md-6 col-lg-4' => '33%',
                        'col-12 col-md-6' => '50%',
                        'col-12 col-lg-8' => '66.66%',
                        'col-12 col-lg-9' => '75%',
                        'col-12' => 'Volle Breite',
                    ),
                ),


                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),

                'alternate_background' => array(
                    'label' => array('Alternative Hintergrundfarbe für Text', 'In HEX angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'alternate_textcolor' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'text' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'longtext' => array(
                    'label' => array('Langtext', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE','tl_class' => 'clr'),

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

                'link' => array(
                    'label' => array('Verlinkung', ''),
                    'inputType' => 'url',
                ),

                'hide_arrow' => array(
                    'label' => array('Link-Symbol ausblenden', ''),
                    'inputType' => 'checkbox',
                ),

                'modal_longtext' => array(
                    'label' => array('Text für modales Fenster', '(überschreibt Verlinkung und öffnet modales Fenster)'),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),

                'modal_image' => array(
                    'label' => array('Bild für modales Fenster', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),


            ),
        ),
    ),
);
