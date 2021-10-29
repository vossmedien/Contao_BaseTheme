<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Boxen mit Icon / Bild, Link, Text & Button', ''),
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

        'kachel' => array(
            'label' => array('Kacheln', ''),
            'elementLabel' => '%s. Kachel',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 10,
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
                        'col-12 col-md-auto' => 'Automatische Breite (füllend)',
                        'col-12 col-md' => 'Breite anhand des Inhalts',
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

                'icon' => array(
                    'label' => array('Alternativ zum Bild Font-Awesome Klasse angeben', 'überschreibt das Bild, z. B. fa-facebook fab'),
                    'inputType' => 'text',
                ),

                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'h1' => 'H1',
                        'h2' => 'H2',
                        'h3' => 'H3',
                        'h4' => 'H4',
                        'h5' => 'H5',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),

                'headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),



                'text' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'text',

                    'eval' => array(
                        'allowHtml' => true,
                        'rte' => 'tinyMCE',
                        'tl_class' => 'clr'
                    ),
                ),

                'link_text' => array(
                    'label' => array(
                        'de' => array('Button-Beschriftung', 'Button befindet sich rechts unter dem Text'),
                    ),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'link_url' => array(
                    'label' => array('Verlinkung der Beschriftung', ''),
                    'inputType' => 'url',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'link_type' => array(
                    'label' => array(
                        'de' => array('Farbe des Buttons', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'btn-primary' => 'Hauptfarbe',
                        'btn-outline-primary' => 'Hauptfarbe (Outline)',
                        'btn-secondary' => 'Sekundär-Farbe',
                        'btn-outline-secondary' => 'Sekundär-Farbe (Outline)',
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
