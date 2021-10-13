<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Box mit Titel und Subtitle sowie Galerie je Box', ''),
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

        'box' => array(
            'label' => array('Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 12,
            'fields' => array(
                'image' => array(
                    'label' => array('Bild', 'oberhalb der Box'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),

                'images' => array(
                    'label' => array('Bilder für Galerie', 'falls Galerie in Lightbox gewünscht, Verlinkung dann nicht mehr möglich'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => true,
                        'fieldType' => 'checkbox',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),

                'title' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'text',
                ),

                'subtitle' => array(
                    'label' => array('Subtitle', ''),
                    'inputType' => 'text',
                ),

                'url' => array(
                    'label' => array('Link', 'nur möglich, wenn keine Galerie-Bilder ausgewählt sind, Link wird dann ignoriert'),
                    'inputType' => 'url',
                ),
            ),
        ),

    ),
);
