<?php


use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

$config = array(
    'label' => array('Custom | Aufzählung (enumeration)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'layout_type' => array(
            'label' => array('Spaltenlayout', 'Wie viele Spalten sollen für die Aufzählung verwendet werden?'),
            'inputType' => 'select',
            'options' => array(
                '1_column' => '1-spaltig',
                '2_columns' => '2-spaltig',
                '3_columns' => '3-spaltig',
            ),
            'eval' => array('tl_class' => 'w50 clr', 'chosen' => true),
        ),
        'enumeration_type' => array(
            'label' => array('Aufzählungstyp', 'Wähle den Stil der Aufzählungspunkte.'),
            'inputType' => 'select',
            'options' => array(
                'dot' => 'Dot mit Underline',
                'box' => 'Box mit Zahl',
            ),
            'eval' => array('tl_class' => 'w50', 'chosen' => true),
        ),
        'wrapper_class' => array(
            'label' => array('Zusätzliche CSS-Klasse für Wrapper', 'Optionale Klasse für den direkten Wrapper der Aufzählungsliste.'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'clr'),
        ),

        'animation_group' => array(
            'label' => array('Animationseinstellungen', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
        ),
        'animate_items_individually' => array(
            'label' => array('Aufzählungspunkte einzeln animieren', 'Wenn nicht aktiviert, wird der gesamte Aufzählungsblock animiert.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'animation_enumeration' => array(
            'label' => array('Animation Aufzählung', 'Animation für die Aufzählungspunkte oder den Aufzählungsblock.'),
            'inputType' => 'select',
            'options_callback' => function() {
                 return \Vsm\VsmHelperTools\Helper\GlobalElementConfig::getAnimations();
            },
            'eval' => array('tl_class' => 'w50', 'chosen' => true, 'includeBlankOption' => true),
        ),
        'animation_text_right' => array(
            'label' => array('Animation rechter Text', 'Animation für den optionalen Textblock rechts (nur bei 1-spaltigem Layout).'),
            'inputType' => 'select',
            'options_callback' => function() {
                 return \Vsm\VsmHelperTools\Helper\GlobalElementConfig::getAnimations();
            },
            'eval' => array('tl_class' => 'w50', 'chosen' => true, 'includeBlankOption' => true),
            'dependsOn' => array(
                'field' => 'layout_type',
                'value' => '1_column',
            ),
        ),

        // Aufzählungspunkte pro Spalte
        'column_count_1_column' => array(
            'label' => array('Anzahl Aufzählungspunkte (1-spaltig)', 'Definiere die Anzahl der Punkte. Wenn leer, werden alle Punkte in dieser Spalte angezeigt.'),
            'inputType' => 'number',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'digit', 'minval' => 1),
            'dependsOn' => array(
                'field' => 'layout_type',
                'value' => '1_column',
            ),
        ),
        'column_count_2_columns' => array(
            'label' => array('Anzahl Aufzählungspunkte pro Spalte (2-spaltig)', 'Definiere die Anzahl der Punkte pro Spalte. Wenn leer, werden Punkte gleichmäßig verteilt.'),
            'inputType' => 'number',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'digit', 'minval' => 1),
            'dependsOn' => array(
                'field' => 'layout_type',
                'value' => '2_columns',
            ),
        ),
        'column_count_3_columns' => array(
            'label' => array('Anzahl Aufzählungspunkte pro Spalte (3-spaltig)', 'Definiere die Anzahl der Punkte pro Spalte. Wenn leer, werden Punkte gleichmäßig verteilt.'),
            'inputType' => 'number',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'digit', 'minval' => 1),
            'dependsOn' => array(
                'field' => 'layout_type',
                'value' => '3_columns',
            ),
        ),


        // Box Styling (abhängig von enumeration_type == 'box')
        'box_styling_group' => array(
            'label' => array('Box-Design (Nur für Typ "Box mit Zahl")', ''),
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'enumeration_type',
                'value' => 'box',
            ),
        ),
        'box_background_color' => array(
            'label' => array('Hintergrundfarbe der Box', 'z.B. #FFFFFF oder var(--bs-primary)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'enumeration_type',
                'value' => 'box',
            ),
        ),
        'box_text_color' => array(
            'label' => array('Schriftfarbe der Box-Nummer', 'z.B. #000000 oder var(--secondary-color)'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'enumeration_type',
                'value' => 'box',
            ),
        ),


        'enumeration_items' => array(
            'label' => array('Aufzählungspunkte', ''),
            'elementLabel' => '%s. Aufzählungspunkt',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(
                'item_headline' => array(
                    'label' => array('Überschrift (optional)', 'Optionale Überschrift für den Aufzählungspunkt'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'item_text' => array(
                    'label' => array('Text', 'Text des Aufzählungspunktes'),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),
            ),
        ),

        // Rechte Spalte für 1-spaltiges Layout
        'single_column_right_text_group' => array(
            'label' => array('Optionaler Textblock rechts (nur bei 1-spaltigem Layout)', 'Wird nur im Frontend angezeigt, wenn das Textfeld unten ausgefüllt ist.'),
            'inputType' => 'group',
            'dependsOn' => array(
                'field' => 'layout_type',
                'value' => '1_column',
            ),
            'eval' => array('tl_class' => 'clr'),
        ),
        'topline_single_column' => array(
            'label' => array('Topline', 'Text oberhalb der Überschrift des rechten Textfeldes'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'headline_single_column' => array(
            'label' => array('Überschrift', 'Überschrift des rechten Textfeldes'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'subline_single_column' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift des rechten Textfeldes'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'hl_single_column' => array(
            'label' => array('Typ der Überschrift', 'HTML-Tag für die Überschrift'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'default' => 'h2',
            'eval' => array('tl_class' => 'w50', 'chosen' => true),
        ),
        'text_single_column' => array(
            'label' => array('Text für rechte Spalte', 'Wenn dieses Feld ausgefüllt ist, wird der Textblock rechts neben der einspaltigen Aufzählung angezeigt.'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);