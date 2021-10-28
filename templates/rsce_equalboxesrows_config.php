<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | 50% Spalte & mehrere Boxen auf gleicher Höhe auf anderer Spalte', ''),
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

        'row' => array(
            'label' => array('Zeilen', ''),
            'elementLabel' => '%s. Zeile',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 10,
            'fields' => array(
                'swapcolumns' => array(
                    'label' => array('Spalten tauschen', ''),
                    'inputType' => 'checkbox',
                ),


                'main_image' => array(
                    'label' => array('Boxen-Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg',
                    ),
                ),

                'main_url' => array(
                    'label' => array('Link', ''),
                    'inputType' => 'url',
                ),

                'main_headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'h1' => 'H1',
                        'h2' => 'H2',
                        'h3' => 'H3',
                        'h4' => 'H4',
                        'h5' => 'H5',
                    ),

                ),

                'main_headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true),
                ),

                'main_onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                ),

                'main_subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                ),

                'main_content' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),



                'boxes' => array(
                    'label' => array('Boxen (rechts)', ''),
                    'elementLabel' => '%s. Box',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 4,
                    'fields' => array(
                        'image' => array(
                            'label' => array('Boxen-Bild', ''),
                            'inputType' => 'fileTree',
                            'eval' => array(
                                'multiple' => false,
                                'fieldType' => 'radio',
                                'filesOnly' => true,
                                'extensions' => 'jpg,jpeg,png,svg',
                            ),
                        ),

                        'url' => array(
                            'label' => array('Link', ''),
                            'inputType' => 'url',
                        ),

                        'headline_type' => array(
                            'label' => array(
                                'de' => array('Typ der Überschrift', ''),
                            ),
                            'inputType' => 'select',
                            'options' => array(
                                'h1' => 'H1',
                                'h2' => 'H2',
                                'h3' => 'H3',
                                'h4' => 'H4',
                                'h5' => 'H5',
                            ),
                        ),

                        'headline' => array(
                            'label' => array('Überschrift', ''),
                            'inputType' => 'text',
                            'eval' => array('allowHtml' => true),
                        ),

                        'onlystyle' => array(
                            'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                            'inputType' => 'checkbox',
                        ),

                        'subline' => array(
                            'label' => array('Subline', ''),
                            'inputType' => 'text',
                        ),

                        'content' => array(
                            'label' => array('Text', ''),
                            'inputType' => 'textarea',
                            'eval' => array('rte' => 'tinyMCE'),
                        ),


                    ),
                ),

            ),
        ),

    ),
);
