<?php
// rsce_my_element_config.php
// @TODO FullWidthStoerer und TeaserStripe zusammenfassen
return array(
    'label' => array('Custom | Full-Width Störer', ''),
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
            'label' => array('Bild', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg',
            ),
        ),
        'ce_headline' => array(
            'label' => array('Überschrift', ''),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true),
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
        'ce_subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
        ),
        'content' => array(
            'label' => array('Text', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE'),
        ),
        'button_text' => array(
            'label' => array('Button-Beschriftung', ''),
            'inputType' => 'text',
        ),
        'button_url' => array(
            'label' => array('Button-Link', ''),
            'inputType' => 'url',
        ),

        'button_type' => array(
            'label' => array(
                'de' => array('Optik des Buttons', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'btn-primary' => 'Hauptfarbe',
                'btn-outline-primary' => 'Hauptfarbe (Outline)',
                'btn-secondary' => 'Sekundär-Farbe',
                'btn-outline-secondary' => 'Sekundär-Farbe (Outline)',
                'btn-link with-arrow' => 'Link-Optik mit Pfeilen',
                'btn-outline-black' => 'Transparenter Button mit schwarzer Schrift und Rahmen',
                'btn-white' => 'Weißer Button mit schwarzer Schrift',
            ),
            'eval' => array('tl_class' => 'w50'),
        ),

        'button_size' => array(
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
);
