<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Bubble-List', ''),
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

        'backgroundcolor' => array(
            'label' => array('Hintergrundfarbe', 'Im Hexformat, z. B. #000 für schwarz (Standard: weiß)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'linkcolor' => array(
            'label' => array('Schriftfarbe', 'Im Hexformat, z. B. #000 für schwarz (Standard: Hauptfarbe)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'galery' => array(
            'label' => array('Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 99,
            'fields' => array(
                'img' => array(
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
                    'label' => array('Text', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'url' => array(
                    'label' => array('URL', ''),
                    'inputType' => 'url',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
        ),
    ),
);
