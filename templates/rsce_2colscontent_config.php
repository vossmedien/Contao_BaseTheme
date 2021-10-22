<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | 2 Spaltiger Inhalt', ''),
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
            'maxItems' => 20,
            'fields' => array(
                'asbox' => array(
                    'label' => array('Beide Spalten in einer Box mit Schatten darstellen', ''),
                    'inputType' => 'checkbox',
                ),

                'swapcolumns' => array(
                    'label' => array('Spalten tauschen', ''),
                    'inputType' => 'checkbox',
                ),

                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', 'für linke Spalte'),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'h1' => 'H1 (Haupt-Headline für SEO, darf nur 1x vorkommen)',
                        'h2' => 'H2 (Sollte H1 thematisch untergeordnet sein)',
                        'h3' => 'H3 (Sollte H2 thematisch untergeordnet sein)',
                        'h4' => 'H4',
                        'h5' => 'H5',
                    ),
                ),

                'headline' => array(
                    'label' => array('Überschrift', 'für linke Spalte'),
                    'inputType' => 'text',
                ),

                'aboverow' => array(
                    'label' => array('Überschrift oberhab der beiden Spalten anzeigen','und aus der linken Spalte nehmen'),
                    'inputType' => 'checkbox',
                ),


                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                ),

                'leftcol_subline' => array(
                    'label' => array('Subline für linke Spalte', ''),
                    'inputType' => 'text',
                ),

                'leftcol_text' => array(
                    'label' => array('Text für linke Spalte', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),

                'leftcol_image' => array(
                    'label' => array('Bild für linke Spalte', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),


                'rightcol_text' => array(
                    'label' => array('Text für rechte Spalte', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),

                'link_text' => array(
                    'label' => array(
                        'de' => array('Button-Beschriftung', 'Button befindet sich rechts unter dem Text'),
                    ),
                    'inputType' => 'text',
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
        ),

    ),
);
