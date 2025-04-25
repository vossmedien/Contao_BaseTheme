<?php

use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

return array(
    'label' => array('Custom | Menü Boxen', 'Element zur Darstellung von klickbaren Boxen mit Bild, Text und Link.'),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'), // Headline optional hinzugefügt
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'image_size' => array(
            'label' => array('Bildgröße', 'Hier können Sie die Abmessungen der Bilder in den Boxen festlegen.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'clr'
            ),
        ),
        'background_color' => array(
            'label' => array('Hintergrundfarbe der Boxen', 'Standard: #ECF0F2 mit 90% Opazität'),
            'inputType' => 'text',
            'default' => 'rgba(236, 240, 242, 0.9)',
            'eval' => array('colorpicker' => true, 'tl_class' => 'w50 clr'),
        ),
        'text_color' => array(
            'label' => array('Textfarbe der Boxen', 'Standard: var(--bs-body-color)'),
            'inputType' => 'text',
            'default' => 'var(--bs-body-color)',
            'eval' => array('colorpicker' => true, 'tl_class' => 'w50'),
        ),
         'font_size' => array(
            'label' => array('Schriftgröße des Textes', 'Standard: 20px'),
            'inputType' => 'text',
            'default' => '20px',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'boxes' => array(
            'label' => array('Menü-Boxen', ''),
            'elementLabel' => '%s. Box',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 12, // Flexibel, wie viele Boxen hinzugefügt werden können
            'eval' => array('tl_class' => 'clr'),
            'fields' => array(
                 'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation für diese Box', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true')
                ),
                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'filesOnly' => true,
                        'extensions' => \Contao\Config::get('validImageTypes'),
                        'fieldType' => 'radio',
                        'mandatory' => true,
                        'tl_class' => 'clr'
                    ),
                ),
                'title' => array(
                    'label' => array('Titel', 'Text in der Box'),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'link' => array(
                     'label' => array('Verlinkung', 'Interne Seite auswählen'),
                     'inputType' => 'pageTree',
                     'eval' => array('mandatory' => true, 'fieldType'=>'radio', 'tl_class'=>'w50 clr'),
                ),
                'column_class' => array(
                    'label' => array('Breite der Box (Bootstrap Grid)', 'Wählen Sie die Spaltenbreite für diese Box.'),
                    'inputType' => 'select',
                    'options' => array(
                        'col-12' => 'Ganze Breite (12/12)',
                        'col-md-8' => 'Zwei Drittel (8/12)',
                        'col-md-6' => 'Halbe Breite (6/12)',
                        'col-md-4' => 'Ein Drittel (4/12)',
                        'col-md-3' => 'Ein Viertel (3/12)',
                    ),
                    'default' => 'col-md-4',
                    'eval' => array('tl_class' => 'w50'),
                ),
                 'vertical_stack_group' => array(
                    'label' => array('Vertikale Stapelgruppe', 'Optional. Geben Sie eine Zahl ein (z.B. 1, 2). Boxen mit der gleichen Zahl in derselben Spalte werden vertikal gestapelt, wenn das Layout dies erfordert (experimentell).'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50 clr'),
                ),
            ),
        ),
    ),
); 