<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Slider mit Video in modaler Box', ''),
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


        'fullwidth' => array(
            'label' => array('Boxen auf die volle Breite des Viewports anzeigen', ''),
            'inputType' => 'checkbox',
        ),

        'columns' => array(
            'label' => array(
                'de' => array('Anzahl der Spalten', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                '2' => '2 Spaltig',
                '3' => '3 Spaltig',
                '4' => '4 Spaltig',
                '6' => '6 Spaltig',
            ),
        ),

        'gutter' => array(
            'label' => array('Spaltenabstand', ''),
            'inputType' => 'text',
        ),

        'elements' => array(
            'label' => array('Elemente', ''),
            'elementLabel' => '%s. Element',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(
                'video_id' => array(
                    'label' => array('Youtube-Video ID', 'in der URL nach /watch?v='),
                    'inputType' => 'text',
                ),

                'open_tab' => array(
                    'label' => array('Video in neuen Tab öffnen', 'und nicht in modalem Fenster'),
                    'inputType' => 'checkbox',
                ),

                'image' => array(
                    'label' => array('Vorschaubild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),

                'text' => array(
                    'label' => array('Bezeichnung', ''),
                    'inputType' => 'text',
                ),
            ),
        ),
    ),
);
