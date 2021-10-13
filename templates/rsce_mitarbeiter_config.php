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
                'name' => array(
                    'label' => array('Name', ''),
                    'inputType' => 'text',
                ),
                'beschreibung' => array(
                    'label' => array('Kurzbeschreibung', ''),
                    'inputType' => 'text',
                ),
            ),
        ),
    ),
);
