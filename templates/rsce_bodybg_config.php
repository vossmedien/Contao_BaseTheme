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

        'element_type' => array(
            'label' => array('Darstellungstyp', ''),
            'inputType' => 'radio',
            'options' => array(
                '1' => 'Slider',
                '2' => 'Einzelnes Bild / Video',
            ),
        ),

        'settings_slider' => array(
            'label' => array('Slider-Einstellungen', ''),
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '1',
            ),
        ),

        'multiSRC' => array(
            'inputType' => 'standardField',

            'eval' => array(
                'multiple' => true,
                'fieldType' => 'checkbox',
                'orderField' => 'orderSRC',
                'files' => true,
                'mandatory' => false,
                'isGallery' => true,
                'extensions' => 'jpg,jpeg,png,svg',
            ),
        ),

        'slide_effect' => array(
            'label' => array(
                'de' => array('Slide-Effekt', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'slide' => 'Slide (Standard)',
                'fade' => 'Fade',
                'coverflow' => 'Coverflow',
                'flip' => 'Flip',
                'cube' => 'Cube',

            ),
            'eval' => array('tl_class' => 'w50'),
        ),


        'autoplay_time' => array(
            'label' => array('Autoplay-Zyklus', 'nach wie viel MS soll zum nächsten Slide gewechselt werden, Standard: 7500'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'autoplay',
            ),
        ),


        'transition_time' => array(
            'label' => array('Animationszeit in ms', 'Standard: 1500'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),



        'settings_single' => array(
            'label' => array('Einstellungen', ''),
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '2',
            ),
        ),

        'only_article' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Bild als Hintergrund für den Abschnitt und nicht für den kompletten Body',
            ),

        ),

        'image' => array(
            'label' => array('Bild / Video', ""),
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
