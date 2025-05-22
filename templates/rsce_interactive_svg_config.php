<?php

use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

// Annahme: Diese Klasse existiert und wird benötigt

return array(
    'label' => array('Custom | Interaktives SVG mit Links', 'Element mit SVG-Grafik und verknüpften Links'),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        // --- Globale Überschrift oberhalb des Elements ---
        'overall_headline_group' => array(
            'label' => ['Überschrift oberhalb des Elements'],
            'inputType' => 'group',
        ),
        'overall_topline' => array(
            'label' => array('Topline (oberhalb)', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 ', 'allowHtml' => true),
        ),

        'overall_subline' => array(
            'label' => array('Subline (oberhalb)', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 ', 'allowHtml' => true),
        ),


        'overall_headline' => array(
            'label' => array('Überschrift (oberhalb)', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'overall_headline_type' => array(
            'label' => array('Typ der Überschrift (oberhalb)', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'default' => 'h1', // Oft ist die oberste Headline eine H1
            'eval' => array('tl_class' => 'w50'),
        ),

        // --- SVG Einstellungen ---
        'svg_code_group' => array(
            'label' => ['SVG Einstellungen'],
            'inputType' => 'group',
        ),
        'svg_code' => array(
            'label' => array('SVG-Code', 'Hier den vollständigen SVG-Code einfügen.'),
            'inputType' => 'textarea',
            'eval' => array('tl_class' => 'clr', 'allowHtml' => true, 'preserveTags' => true, 'decodeEntities' => false, 'style' => 'max-height: 200px; font-family: monospace;'),
        ),
        'hover_color' => array(
            'label' => array('SVG Hover-Farbe', 'Farbe, die ein SVG-Pfad beim Hover erhält. z.B. #FF0000, red oder var(--meine-css-variable)'),
            'inputType' => 'text',
            'eval' => array('maxlength' => 255, 'colorpicker' => true, 'tl_class' => 'w50 wizard'),
        ),
        'animation_type_svg' => array(
            'label' => array('Animation: SVG-Spalte', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        // --- Rechte Spalte: Inhalt ---
        'content_group' => array(
            'label' => ['Inhalt rechte Spalte'],
            'inputType' => 'group',
        ),
                'animation_type_content_headline' => array(
            'label' => array('Animation: Überschrift (rechte Spalte)', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => ' clr'),
        ),

        'topline' => array(
            'label' => array('Topline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 ', 'allowHtml' => true),
        ),
           'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 ', 'allowHtml' => true),
        ),

        'headline' => array(
            'label' => array('Überschrift', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'mandatory' => false),
        ),
        'headline_type' => array(
            'label' => array('Typ der Überschrift', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'default' => 'h2',
            'eval' => array('tl_class' => 'w50'),
        ),


        'text' => array(
            'label' => array('Text', 'Textblock unter der Überschrift in der rechten Spalte.'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),
        'animation_type_content_text' => array(
            'label' => array('Animation: Text (rechte Spalte)', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        // --- Links für rechte Spalte ---
        'links_group' => array(
            'label' => ['Verlinkungen (rechte Spalte)'],
            'inputType' => 'group',
        ),
        'link_button_type' => array(
            'label' => array('Button-Stil für Links', 'Globaler Stil für alle Links dieses Elements.'),
            'inputType' => 'select',
                'options' => GlobalElementConfig::getButtonTypes(),
            'default' => 'btn-primary',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'links' => array(
            'label' => array('Links', 'Liste von Links mit zugehöriger SVG-Klasse.'),
            'elementLabel' => '%s. Link',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 20, // Anpassbar
            'eval' => array('tl_class' => 'clr'),
            'fields' => array(
                'link_title' => array(
                    'label' => array('Link-Titel', 'Text des Links'),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'link_href' => array(
                    'label' => array('Link-URL', 'Ziel des Links (z.B. {{link_url::ID}})'),
                    'inputType' => 'url',
                    'eval' => array('mandatory' => true, 'rgxp' => 'url', 'decodeEntities' => true, 'tl_class' => 'w50'),
                ),
                'link_target' => array(
                    'label' => array('In neuem Tab öffnen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50 clr'),
                ),
                'svg_class' => array(
                    'label' => array('SVG-Klasse', 'CSS-Klasse im SVG, die mit diesem Link verknüpft ist.'),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
            ),
        ),
        'animation_type_content_links' => array(
            'label' => array('Animation: Link-Bereich (rechte Spalte)', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50'),
        ),
        'animate_links_individually' => array(
            'label' => array('Links einzeln animieren', 'Wenn ausgewählt, erhält jeder Link die gewählte Animation. Sonst der gesamte Link-Block.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        // --- Layout Optionen entfernt animation_type_element ---
        'layout_options_group' => array(
            'label' => ['Layout Optionen'],
            'inputType' => 'group',
        ),
        'columns_container_css_class' => array(
            'label' => array('Zusätzliche CSS-Klasse für den inneren Container', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
    ),
); 