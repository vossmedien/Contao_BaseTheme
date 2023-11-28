<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Hintergrund für Website / Artikel (bodybg)', ''),
    'types' => array('content'),
    'contentCategory' => 'texts',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
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
                '3' => 'Eigenes CSS (z. B. für Gradient / Verlauf)',
            ),
        ),


        'only_article' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Hintergrund für den Abschnitt und nicht für den kompletten Body',
            ),
        ),


        'is_between' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Abstand zum Artikel davor entfernen, so dass Abschrägungen ggf. zusammengeführt werden und Artikel aneinander liegen',
            ),
            'dependsOn' => array(
                'field' => 'only_article',
                'value' => '1',
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
                'extensions' => 'jpg,jpeg,png,svg,webp',
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
            'eval' => array('tl_class' => 'w50'),
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


        'darken_image' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Hintergrundbild zusätzlich abdunkeln',
            ),
        ),


        'image' => array(
            'label' => array('Bild / Video', ""),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4,svg',
            ),
        ),

        'image_mobile' => array(
            'label' => array('Alternative für Mobile', ""),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
            ),
        ),

        'fit_image' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Bild auf Breite und Höhe des Bereichs strecken',
            ),
        ),

        'settings_code' => array(
            'label' => array('Einstellungen', ''),
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => '3',
            ),
        ),

        'css' => array(
            'label' => array('Eigener Code', 'wird als inline-style innerhalb von "background: #WERT#" eingebunden. Falls ausgefüllt, wird dieser Wert auch für die Abschrägungen genutzt.'),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true),
        ),

        'settings_diagonal' => array(
            'label' => array('Abschrägung', ''),
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'only_article',
                'value' => '1',
            ),
        ),

        'activate' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Abschrägung aktiviere. Wichtig: deaktiviert parallax-Effekt',
            ),
        ),

        'winkel' => array(
            'label' => array('Abschrägungswinkel', 'Geben Sie einen Wert zwischen -5 und 5 ein. Standard: 2.5 bzw. Inhalt von var(--base-skew)'),
            'inputType' => 'text',
            'eval' => array(
                'rgxp' => 'digit', // Erlaubt Zahlen einschließlich negativer Werte
                'maxlength' => 2, // Erlaubt bis zu 3 Zeichen (z.B. "-5" oder "5")
                'tl_class' => 'w50'
            ),
            'sql' => "varchar(2) NOT NULL default ''"
        ),


        /*
            'skew_fix' => array(
                'label' => array('', ''),
                'inputType' => 'checkbox',
                'options' => array(
                    '1' => 'FIX für Abschrägung (Evtl. auch bei Abstandsproblemen testen)',
                ),
            ),

            'padding_fix' => array(
                'label' => array('', ''),
                'inputType' => 'checkbox',
                'options' => array(
                    '1' => 'FIX für Abstandsprobleme (zu klein / zu groß)',
                ),
            ),

             */


    ),
);
