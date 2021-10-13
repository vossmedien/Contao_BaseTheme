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
                'text' => array(
                    'label' => array('Beschriftung', ''),
                    'inputType' => 'text',
                ),

                'longtext' => array(
                    'label' => array('Langtext', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),

                'link' => array(
                    'label' => array('Verlinkung', ''),
                    'inputType' => 'url',
                ),
            ),
        ),
    ),
);
