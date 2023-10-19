<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Hintergrund für Website / Abschnitt', ''),
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
                'extensions' => 'jpg,jpeg,png,svg',
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
        ),

        'activate' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Abschrägung aktivieren',
            ),
        ),

        'winkel' => array(
            'label' => array('Abschrägungswinkel', 'Standard: -5, nutze beispielsweise 5 um den Winkel umzukehren'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'activate',
            ),
        ),


        'is_between' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Hintergrund auffüllen, falls Zwischenräume vorhanden',
            ),
        ),

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


        'deactivate_bottom' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Abschrägung unten deaktivieren',
            ),
            'dependsOn' => array(
                'field' => 'activate',
            ),
        ),


        'deactivate_top' => array(
            'label' => array('', ''),
            'inputType' => 'checkbox',
            'options' => array(
                '1' => 'Abschrägung oben deaktivieren',
            ),
            'dependsOn' => array(
                'field' => 'activate',
            ),
        ),


    ),
);
