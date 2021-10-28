<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Zeilen mit mehrspaltigen Inhalte', ''),
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
                    'label' => array('Zeile in einer Box darstellen', ''),
                    'inputType' => 'checkbox',
                ),

                'swapcolumns' => array(
                    'label' => array('Spaltenreihenfolge umkehren', ''),
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

                'ce_headline' => array(
                    'label' => array('Überschrift', 'für linke Spalte'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'ce_subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),




                'columns' => array(
                    'label' => array('Spalten', ''),
                    'elementLabel' => '%s. Spalte',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 20,
                    'fields' => array(

                        'column_width' => array(
                            'label' => array(
                                'de' => array('Spaltenbreite', ''),
                            ),
                            'inputType' => 'select',
                            'options' => array(
                                'col-12 col-md-6 col-lg-3' => '25%',
                                'col-12 col-md-6 col-lg-4' => '33%',
                                'col-12 col-md-6' => '50%',
                                'col-12 col-lg-8' => '66.66%',
                                'col-12 col-lg-9' => '75%',
                                'col-12' => 'Volle Breite',
                                'col-12 col-md' => 'Automatische Breite (füllend)',
                                'col-12 col-md-auto' => 'Breite anhand des Inhalts',
                            ),
                        ),

                        'asbox' => array(
                            'label' => array('Spalte in einer Box darstellen', ''),
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
                            'eval' => array('tl_class' => 'w50'),
                        ),

                        'subline' => array(
                            'label' => array('Subline', ''),
                            'inputType' => 'text',
                            'eval' => array('tl_class' => 'w50'),
                        ),

                        'onlystyle' => array(
                            'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                            'inputType' => 'checkbox',
                            'eval' => array('tl_class' => 'clr'),
                        ),





                        'text' => array(
                            'label' => array('Text', ''),
                            'inputType' => 'textarea',
                            'eval' => array('rte' => 'tinyMCE'),
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

                        'image_position' => array(
                            'label' => array(
                                'de' => array('Bildposition', ''),
                            ),
                            'inputType' => 'select',
                            'options' => array(
                                'image_above' => 'Oberhalb von Text',
                                'image_below' => 'Unterhalb von Text',
                            ),
                        ),


                        'link_text' => array(
                            'label' => array(
                                'de' => array('Button-Beschriftung', 'Button befindet sich rechts unter dem Text'),
                            ),
                            'inputType' => 'text',
                        ),

                        'link_url' => array(
                            'label' => array('Verlinkung der Beschriftung', ''),
                            'inputType' => 'url',
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
                            'eval' => array('tl_class' => 'w50'),
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
                            'eval' => array('tl_class' => 'w50'),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
