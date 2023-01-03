<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | WORK-IN-PROGRESS --- Fullpage-Slider', ''),
    'types' => array('content'),
    'contentCategory' => 'texts',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(

        'slides' => array(
            'label' => array('Slides', ''),
            'elementLabel' => '%s. Slide',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 99,
            'fields' => array(),
        ),
    ),
);
