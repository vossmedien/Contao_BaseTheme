<?php
// rsce_boxes_config.php
return array(
    'label' => array('Custom | Formular :: Image-Checkbox mit Headine, Text, Bild & Preis', ''),
    'types' => array('form'),
    'contentCategory' => 'texts',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ), 
    'fields' => array(
        'headline' => array(
            'label' => array('Headine', ''),
            'inputType' => 'text',
        ),

        'headline_type' => array(
            'label' => array(
                'de' => array('Typ der Überschrift', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'h1' => 'H1 (Haupt-Headline für SEO, darf nur 1x vorkommen)',
                'h2' => 'H2 (Sollte H1 thematisch untergeordnet sein)',
                'h3' => 'H3 (Sollte H2 thematisch untergeordnet sein)',
                'h4' => 'H4',
                'h5' => 'H5',
            ),
        ),

        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
        ),

        'input_type' => array(
            'label' => array(
                'de' => array('Input-Typ', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'checkbox' => 'Checkbox',
                'radio' => 'Radio',
            ),
        ),

        'name' => array(
            'inputType' => 'standardField',
        ),


        'text_right' => array(
            'label' => array('Text rechts als Box neben den Listing-Elementen', ''),
            'inputType' => 'text',

            'eval' => array(
                'allowHtml' => true,
                'rte' => 'tinyMCE',
                'tl_class' => 'clr'
            ),
        ),

        'inputs' => array(
            'label' => array('Inputs', ''),
            'elementLabel' => '%s. Input',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 99,
            'fields' => array(

                'input_value' => array(
                    'label' => array('Eindeutiger Wert des Inputs', 'einmalig, keine Sonderzeichen'),
                    'inputType' => 'text',
                ),


                'headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                ),

                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'h1' => 'H1 (Haupt-Headline für SEO, darf nur 1x vorkommen)',
                        'h2' => 'H2 (Sollte H1 thematisch untergeordnet sein)',
                        'h3' => 'H3 (Sollte H2 thematisch untergeordnet sein)',
                        'h4' => 'H4',
                        'h5' => 'H5',
                    ),
                ),

                'text_top' => array(
                    'label' => array('Text oberhalb des Bildes', ''),
                    'inputType' => 'text',

                    'eval' => array(
                        'allowHtml' => true,
                        'rte' => 'tinyMCE',
                        'tl_class' => 'clr'
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


                'text_bottom' => array(
                    'label' => array('Text unterhalb des Bildes', ''),
                    'inputType' => 'text',

                    'eval' => array(
                        'allowHtml' => true,
                        'rte' => 'tinyMCE',
                        'tl_class' => 'clr'
                    ),
                ),


                'price' => array(
                    'label' => array('Preis ohne Zeichen', 'das Währungssymbol wird automatisch angehängt'),
                    'inputType' => 'text',
                ),


                'preview_links' => array(
                    'label' => array('Text unterhalb des Preises', ''),
                    'inputType' => 'text',

                    'eval' => array(
                        'allowHtml' => true,
                        'rte' => 'tinyMCE',
                        'tl_class' => 'clr'
                    ),
                ),


            ),
        ),




    ),
);
