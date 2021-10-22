<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Headline, Text & Button mit Bild darüber', ''),
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
            'label' => array('Großes Bild', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg',
            ),
        ),


        'asbox' => array(
            'label' => array('Als Box mit Schatten darstellen', 'Automatisch ausgewählt, wenn Bild ausgewählt wurde'),
            'inputType' => 'checkbox',
        ),

        'headline_type' => array(
            'label' => array(
                'de' => array('Typ der Überschrift', ''),
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
            'label' => array('Überschrift', ''),
            'inputType' => 'text',
        ),

        'onlystyle' => array(
            'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
            'inputType' => 'checkbox',
        ),

        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true),
        ),

        'text' => array(
            'label' => array('Text', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE'),
        ),

        'buttons' => array(
            'label' => array('Buttons', ''),
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 20,
            'fields' => array(
                'link_text' => array(
                    'label' => array('Link-Beschriftung', ''),
                    'inputType' => 'text',
                ),
                'link_url' => array(
                    'label' => array('Verlinkung der Beschriftung', ''),
                    'inputType' => 'url',
                ),

                'link_betreff' => array(
                    'label' => array('Betreffzeile für "mailto:"-Buttons', '(optional, falls Link eine neue Email öffnen soll)'),
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
