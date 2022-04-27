<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Drehende Cards nach Klick', ''),
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

        'rows' => array(
            'label' => array('Cards', ''),
            'elementLabel' => '%s. Card',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(



                'column_width' => array(
                    'label' => array(
                        'de' => array('Inhalts-Spaltenbreite', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12 col-md-6 col-lg-3' => '25% (Standard)',
                        'col-12 col-md-6 col-lg-4' => '33%',
                        'col-12 col-md-6 col-lg-5' => 'ca. 40%',
                        'col-12 col-md-6' => '50%',
                        'col-12 col-lg-8' => '66.66%',
                        'col-12 col-lg-9' => '75%'
                    ),
                ),


                'settings_1' => array(
                    'label' => array('Vorderseite', ''),
                    'inputType' => 'group',
                ),

                'image_front' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                        'tl_class' => 'clr'
                    ),
                ),

                'front_background' => array(
                    'label' => array('Alternative Hintergrundfarbe Vorderseite', 'In HEX oder rgb(a) angeben, ansonsten Hauptfarbe'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'front_textcolor' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),



                'front_textalign' => array(
                    'label' => array(
                        'de' => array('Text-Ausrichtung', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'text-start' => 'Linksbündig',
                        'text-center' => 'Zentriert',
                        'text-end' => 'Rechtsbündig',
                    ),
                    'eval' => array('tl_class' => 'clr'),
                ),


                'front_headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                ),

                'front_content_headline' => array(
                    'label' => array('Überschrift für Textpassage', ''),
                    'inputType' => 'text',
                ),

                'front_content_text' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),

                'settings_2' => array(
                    'label' => array('Rückseite', ''),
                    'inputType' => 'group',
                ),



                'image_back' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                        'tl_class' => 'clr'
                    ),
                ),

                'back_background' => array(
                    'label' => array('Alternative Hintergrundfarbe Rückseite', 'In HEX oder rgb(a) angeben, ansonsten Grau'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'back_textcolor' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'back_textalign' => array(
                    'label' => array(
                        'de' => array('Text-Ausrichtung', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'text-start' => 'Linksbündig',
                        'text-center' => 'Zentriert',
                        'text-end' => 'Rechtsbündig',
                    ),
                    'eval' => array('tl_class' => 'clr'),
                ),

                'back_headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                ),


                'back_content_headline' => array(
                    'label' => array('Überschrift für Textpassage', ''),
                    'inputType' => 'text',
                ),

                'back_content_text' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),


                'settings_3' => array(
                    'label' => array('Button', ''),
                    'inputType' => 'group',
                ),



                'link_text' => array(
                    'label' => array(
                        'de' => array('Button-Beschriftung', 'Button befindet sich rechts unter dem Text'),
                    ),
                    'inputType' => 'text',
                ),
                'link_url' => array(
                    'label' => array('Verlinkung der Beschriftung', 'z. B. mailto:info@gmx.de'),
                    'inputType' => 'url',
                ),

                'link_betreff' => array(
                    'label' => array('Betreffzeile für "mailto:"-Buttons', '(optional, falls Link eine neue Email öffnen soll)'),
                    'inputType' => 'text',
                ),

                'link_type' => array(
                    'label' => array(
                        'de' => array('Optik des Buttons', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                                     'btn-primary' => 'Hauptfarbe',
                                'btn-outline-primary' => 'Hauptfarbe (Outline)',
                                'btn-secondary' => 'Sekundär-Farbe',
                                'btn-outline-secondary' => 'Sekundär-Farbe (Outline)',
                                'btn-link with-arrow' => 'Link-Optik mit Pfeilen',
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
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),

            ),
        ),
    ),
);
