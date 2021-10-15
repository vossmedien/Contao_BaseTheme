<?php
// rsce_my_element_config.php
return array(
    'label' => array('Custom | Abwechselnde Boxen (Box + Bild)', ''),
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

        'box' => array(
            'label' => array('Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 10,
            'fields' => array(

                'reverse' => array(
                    'label' => array('Manuell umkehren', 'Sonst ist jedes zweites Element automatisch auf der anderen Seite'),
                    'inputType' => 'checkbox',
                ),

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

                'versatz' => array(
                    'label' => array('Inhaltsbox Versatz in Pixel', 'Bei 50 wird z. B. die Box um 50 Pixel nach unten geschoben, bei -75 um 75 nach oben.'),
                    'inputType' => 'text',
                ),

                'headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true),
                ),

                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', 'Ausrichtung der Elemente'),
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
            ),
        ),

    ),
);
