<?php
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
return array(
    'label' => array('Custom | Überschrift & Text (headline)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(

        'layout_section_start' => array(
            'label' => ['Layout Optionen'],
            'inputType' => 'group',
        ),

        'two_columns' => array(
            'label' => array('Inhalte zwei-spaltig darstellen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),

        'column_width_content' => array(
            'label' => array('Spaltenbreite: Textspalte', 'Die Headlinespalte nimmt den restlichen Platz ein.'),
            'inputType' => 'select',
            'options' => array(
                '25' => '25%',
                '33' => '33%',
                '50' => '50%',
                '67' => '67%',
                '75' => '75%',
            ),
            'default' => '50',
            'eval' => array('tl_class' => 'w50'),
             'dependsOn' => array(
                'field' => 'two_columns',
             ),
        ),

         'headline_above_columns' => array(
            'label' => array('Headline oberhalb beider Spalten darstellen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
             'dependsOn' => array(
                'field' => 'two_columns',
             ),
        ),

         'reverse_columns' => array(
            'label' => array('Spalten umkehren (Desktop)', 'Auf Mobilgeräten ist die Headline-Spalte immer zuerst.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
             'dependsOn' => array(
                'field' => 'two_columns',
             ),
        ),

         'add_second_content' => array(
            'label' => array('Zweites Inhaltsfeld integrieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr', 'submitOnChange' => true),
             'dependsOn' => array(
                'field' => 'two_columns',
             ),
        ),

         'animation_type_headline_column' => array(
            'label' => array('Animation: Inhalt', 'Animiert den Inhalt (z.B. zweiten Text)'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array( 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'add_second_content',
            ),
        ),

        'second_content' => array(
            'label' => array('Zusätzlicher Text (Headline-Spalte)', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
             'dependsOn' => array(
                 'field' => 'add_second_content',
             ),
        ),

        'content_section_start' => array(
            'label' => ['Inhalts Optionen'],
            'inputType' => 'group',
        ),

        'headline' => array(
            'label' => array('Überschrift', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'headline_type' => array(
            'label' => array('Typ der Überschrift', ''),
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
            'label' => array('Topline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr', 'allowHtml' => true),
        ),

        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),

        ),

        'onlystyle' => array(
            'label' => array('Nur als Überschrift darstellen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'animation_type_headline' => array(
            'label' => array('Animation: Headline', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50')
        ),

        'desc' => array(
            'label' => array('Text (Textspalte)', 'optional'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),

        'animation_type_content' => array(
            'label' => array('Animation: Textspalte', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50 clr')
        ),

        'buttons' => array(
            'label' => array('Buttons (Textspalte)', ''),
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 20,
            'eval' => array('tl_class' => 'clr'),
            'fields' => ButtonHelper::getButtonConfig(),
        ),
    ),
);
