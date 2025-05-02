<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

// rsce_my_element_config.php
return array(
    'label' => array('Custom | Akkordeon (accordion)', ''),
     'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID', 'headline', 'hl'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation für Akkordeon-Items', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'w50 clr')
        ),
        'is_faq' => array(
            'label' => array('Ist FAQ-Element (fügt schema.org Daten hinzu)', 'Aktivieren Sie diese Option, wenn dies ein FAQ-Element ist'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'add_left_column_text' => array(
            'label' => array('Text in linker Spalte hinzufügen', 'Fügt eine zusätzliche Spalte links neben dem Akkordeon hinzu.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr', 'submitOnChange' => true),
        ),
        'left_column_text' => array(
            'label' => array('Text für linke Spalte', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'add_left_column_text',
            )
        ),
         'column_width' => array(
             'label' => array('Breite der Text-Spalte (Bootstrap Grid)', 'Wählen Sie die Spaltenbreite für die linke Textspalte. Das Akkordeon nimmt den Rest ein.'),
             'inputType' => 'select',
             'options' => array(
                 'col-lg-6' => 'Halbe Breite (6/12)',
                 'col-lg-5' => 'Fünf Zwölftel (5/12)',
                 'col-lg-4' => 'Ein Drittel (4/12)',
                 'col-lg-3' => 'Ein Viertel (3/12)',
             ),
             'default' => 'col-lg-4',
             'eval' => array('tl_class' => 'w50'),
             'dependsOn' => array(
                 'field' => 'add_left_column_text',
             )
         ),
        'swap_columns' => array(
            'label' => array('Spalten tauschen', 'Aktivieren, um den Text rechts und das Akkordeon links anzuzeigen.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'add_left_column_text',
            )
        ),

        'elements' => array(
            'label' => array('Akkordeons', ''),
            'elementLabel' => '%s. Akkordeon',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 99,
            'fields' => array(
                'is_open' => array(
                    'label' => array('Akkordeon anfangs geöffnet anzeigen', ''),
                    'inputType' => 'checkbox',
                ),

                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                ),

                'headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
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
                    'eval' => array('tl_class' => 'w50'),
                ),

                'topline' => array(
                    'label' => array('Topline', 'Text oberhalb der Überschrift'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
                ),

                'subline' => array(
                    'label' => array('Subline', 'Text unterhalb der Überschrift'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
                ),

                'desc' => array(
                    'label' => array('Text', 'optional'),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),

                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 20,
                    'eval' => array('tl_class' => 'clr'),


                    'fields' => ButtonHelper::getButtonConfig(),
                ),
            ),
        ),
        'add_show_all_button' => array(
            'label' => array('Button "Alle/Weniger anzeigen" hinzufügen', 'Fügt einen Button hinzu, um alle Akkordeon-Elemente ein-/auszublenden.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'initial_visible_items' => array(
            'label' => array('Initial sichtbare Elemente', 'Anzahl der Akkordeon-Elemente, die initial sichtbar sind.'),
            'inputType' => 'text',
            'default' => 3,
            'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'add_show_all_button',
            ),
        ),
        'show_all_button_text' => array(
            'label' => array('Text für "Alle anzeigen"', ''),
            'inputType' => 'text',
            'default' => 'Alle Fragen anzeigen',
            'eval' => array('tl_class' => 'w50 clr'),
             'dependsOn' => array(
                 'field' => 'add_show_all_button',
             ),
        ),
        'show_less_button_text' => array(
            'label' => array('Text für "Weniger anzeigen"', ''),
            'inputType' => 'text',
            'default' => 'Weniger Fragen anzeigen',
            'eval' => array('tl_class' => 'w50'),
             'dependsOn' => array(
                 'field' => 'add_show_all_button',
             ),
        ),
    ),
);


