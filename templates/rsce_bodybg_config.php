<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Hintergrundbild für Website', ''),
    'types' => array('content'),
    'contentCategory' => 'texts',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(

        'only_article' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Bild als Hintergrund für den Abschnitt und nicht für den kompletten Body',
            )
        ),


        'image' => array(
            'label' => array('Bild / Video', 'Video-Format: MP4'),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4',
            ),
        ),
    ),
);
