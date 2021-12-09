<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Kopfbild mit Text', ''),
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

        'image' => array(
            'label' => array('Bild / Video', 'Video-Format: MP4'),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4,webm,ogv',
                'tl_class' => 'w50'
            ),
        ),

        'mobile_image' => array(
            'label' => array('Bild / Video (Mobile)', 'Video-Format: MP4'),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4,webm,ogv',
                'tl_class' => 'w50'
            ),
        ),

        'diagonal_cut' => array(
            'label' => array('Mit diagonalem Abschluss', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'pull_content' => array(
            'label' => array('Inhalt darunter "hochziehen"', ''),
            'inputType' => 'checkbox',
        ),

        'not_fullheight' => array(
            'label' => array('Nicht auf maximale Höhe strecken', ''),
            'inputType' => 'checkbox',
        ),

        'text_style' => array(
            'label' => array(
                'de' => array('Text-Darstellungstyp', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'style-1' => 'Überschrift auf Hintergrund mit Diagonale legen',
                'style-2' => 'Überschrift in groß darstellen',
            ),
        ),

        'text_color' => array(
            'label' => array('Schriftfarbe als HEX-Wert falls abweichend', 'Standard-Farbe ist die Basis-Textfarbe'),
            'inputType' => 'text',
        ),

        'text_firstline' => array(
            'label' => array('Erste Zeile', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'text_secondline' => array(
            'label' => array('Zweite Zeile', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'link_text' => array(
            'label' => array(
                'de' => array('Button-Beschriftung', 'Button befindet sich rechts unter dem Text'),
            ),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'clr'),
        ),

        'link_type' => array(
            'label' => array(
                'de' => array('Farbe des Buttons', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'btn-primary' => 'Hauptfarbe',
                'btn-outline-primary' => 'Hauptfarbe (Outline)',
                'btn-secondary' => 'Sekundär-Farbe',
                'btn-outline-secondary' => 'Sekundär-Farbe (Outline)',
            ),
        ),

        'link_url' => array(
            'label' => array('Verlinkung der Beschriftung', ''),
            'inputType' => 'url',
        ),

        'link_size' => array(
            'label' => array(
                'de' => array('Größe des Buttons', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                '' => 'Standard',
                'btn-sm' => 'Klein',
                'btn-lg' => 'Groß',
            ),
        ),
    ),
);
