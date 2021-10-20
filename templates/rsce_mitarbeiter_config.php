<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Mitarbeiter', ''),
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

        'mitarbeiter' => array(
            'label' => array('Mitarbeiter', ''),
            'elementLabel' => '%s. Mitarbeiter',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
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


                'bild' => array(
                    'label' => array('Mitarbeiterbild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
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


                'name' => array(
                    'label' => array('Name', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'beschreibung' => array(
                    'label' => array('Kurzbeschreibung', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE','tl_class' => 'clr'),
                ),

            ),
        ),
    ),
);
