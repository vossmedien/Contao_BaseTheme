<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;


// rsce_my_element_config.php
return array(
    'label' => array('Custom | Abwechselnde Boxen (boxenfeld)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'topline' => array(
            'label' => array('Topline', 'Text oberhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'without_container' => array(
            'label' => array('Ohne Container anzeigen', 'Elemente werden am Bildschirmrand ausgerichtet'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),
        'box' => array(
            'label' => array('Zeilen', ''),
            'elementLabel' => '%s. Zeile',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 10,
            'fields' => array(

                'settings_1' => array(
                    'label' => array('Einstellungen', ''),
                    'inputType' => 'group',
                ),

                'reverse' => array(
                    'label' => array('Spalten umkehren', 'Standard: Bild links, Text rechts'),
                    'inputType' => 'checkbox',
                ),

                'settings_2' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'group',
                ),


                'animation_type_image' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
                ),

                'image' => array(
                    'label' => array('Boxen-Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'tl_class' => 'clr',
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    ),
                ),


                'size' => array(
                    'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
                    'inputType' => 'imageSize',
                    'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                        'tl_class' => ' clr'
                    ),
                ),


                'settings_3' => array(
                    'label' => array('Inhalt: Einstellungen', ''),
                    'inputType' => 'group',
                ),

                'animation_content' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
                ),

                'has_shadow' => array(
                    'label' => array('Inhaltsbox hat Schatten', ''),
                    'inputType' => 'checkbox',

                ),


                'column_width' => array(
                    'label' => array(
                        'de' => array('Spaltenbreite für Inhalt', 'Bildspalte (falls vorhanden) füllt den Rest, Standard: 50%'),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12 col-lg-4 col-xl-3' => '25%',
                        'col-12 col-lg-4' => '33%',
                        'col-12 col-lg-6' => '50%',
                        'col-12 col-lg-8' => '66.66%',
                        'col-12 col-lg-9' => '75%',
                        'col-12' => 'Volle Breite',
                        'col-12 col-lg' => 'Automatische Breite (füllend)',
                        'col-12 col-lg-auto' => 'Breite anhand des Inhalts',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),


                'versatz' => array(
                    'label' => array('Inhaltsbox Versatz in Pixel', 'Bei 50 wird z. B. die Box um 50 Pixel nach unten geschoben, bei -75 um 75 nach oben.'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'alt_background' => array(
                    'label' => array('Alternative Hintergrundfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'alt_textcolor' => array(
                    'label' => array('Alternative Textfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'alt_headlinecolor' => array(
                    'label' => array('Alternative Überschriftfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'alt_bordercolor' => array(
                    'label' => array('Alternative Rahmenfarbe', 'In HEX oder rgb(a) angeben'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'settings_content' => array(
                    'label' => array('Inhalt', ''),
                    'inputType' => 'group',
                ),


                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),


                'headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
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
                        'h6' => 'H6',
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),

                'topline' => array(
                    'label' => array('Topline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'subline' => array(
                    'label' => array('Subline', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),


                'content' => array(
                    'label' => array('Text', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),


                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 20,
                    'eval' => array('tl_class' => 'clr'),
                    'fields' => ButtonHelper::getButtonConfig(),
                ),
            ),
        ),
    ),
);
