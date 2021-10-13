<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Boxen mit Zahlen', ''),
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

        'boxes' => array(
            'label' => array('Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(
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

                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                ),

                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),

                'text_left_1' => array(
                    'label' => array('Zahl linke Spalte', ''),
                    'inputType' => 'text',
                ),

                'text_left_2' => array(
                    'label' => array('Mengenangabe', ''),
                    'inputType' => 'text',
                ),

                'text_right_1' => array(
                    'label' => array('Text rechte Spalte (oben)', ''),
                    'inputType' => 'text',
                ),

                'text_right_2' => array(
                    'label' => array('Text rechte Spalte (unten)', ''),
                    'inputType' => 'text',
                ),
            ),
        ),
    ),
);
