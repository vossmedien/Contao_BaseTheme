<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Zeilen mit Spalten', ''),
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
            'label' => array('Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(
                'headline_image' => array(
                    'label' => array('Bild links neben Headline', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
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

                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                ),

                'headline_color' => array(
                    'label' => array(
                        'de' => array('Farbe der Headline und Border', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'standard' => 'Standard',
                        'blue' => 'Blau',
                        'lila' => 'Lila',
                        'pink' => 'Pink',
                        'yellow' => 'Gelb',
                        'green' => 'Grün',
                        'red' => 'Rot',
                        'mixed' => 'Blau/Lila',
                    ),
                ),

                'colored_row' => array(
                    'label' => array('Zeile blau hinterlegen', ''),
                    'inputType' => 'checkbox',
                ),


                'cols' => array(
                    'label' => array('Spalten', ''),
                    'elementLabel' => '%s. Spalte',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 999,
                    'fields' => array(

                        'headline' => array(
                            'label' => array('Spaltenbezeichnung', ''),
                            'inputType' => 'text',
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

                        'text_1' => array(
                            'label' => array('1. Zeile', ''),
                            'inputType' => 'text',
                        ),

                        'text_2' => array(
                            'label' => array('2. Zeile', ''),
                            'inputType' => 'text',
                        ),
                    ),
                ),
            ),
        ),
    ),
);
