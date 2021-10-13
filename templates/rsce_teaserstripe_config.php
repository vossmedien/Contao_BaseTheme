<?php
// rsce_boxes_config.php
return array(
    'label' => array('Custom | Streifen mit Text auf volle Breite', ''),
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
            'label' => array('Überschrift für Streifen', ''),
            'inputType' => 'text',
        ),

        'ce_subline' => array(
            'label' => array('Subline für Streifen', ''),
            'inputType' => 'text',
        ),

        'text' => array(
            'label' => array('Text', ''),
            'inputType' => 'text',

            'eval' => array(
                'allowHtml' => true,
                'rte' => 'tinyMCE',
                'tl_class' => 'clr'
            ),
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
                'btn-secondary' => 'Sekundär-Farbe',
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
