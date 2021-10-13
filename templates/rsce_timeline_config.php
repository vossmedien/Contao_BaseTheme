<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Timeline', ''),
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
            'label' => array('Reihen', ''),
            'elementLabel' => '%s. Reihe',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 999,
            'fields' => array(
                'year' => array(
                    'label' => array('Jahr', ''),
                    'inputType' => 'text',
                ),

                'description' => array(
                    'label' => array('Was ist passiert', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),

            ),
        ),

    ),
);
