<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

//rsce_boxes_config.php
return array(
    'label' => array('Custom | Full-Width Störer (fullwidthstoerer)', ''),
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
        ), 'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'settings' => array(
            'label' => array('Einstellungen', ''),
            'inputType' => 'group',
        ),

        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),

        'nocolumns' => array(
            'label' => array('Text & Button untereinander anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'image' => array(
            'label' => array('Hintergrundbild', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png',
                'tl_class' => 'clr'
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
                'tl_class' => 'clr'
            ),
        ),


        'alternate_background_color' => array(
            'label' => array('Hintergrundfarbe', 'In HEX oder rgb(a) angeben'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'text_color' => array(
            'label' => array('Alternative Textfarbe', 'In HEX oder rgb(a) angeben'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'settings_content' => array(
            'label' => array('Inhalte', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
        ),


        'ce_topline' => array(
            'label' => array('Topline für Streifen', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'ce_subline' => array(
            'label' => array('Subline für Streifen', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'ce_headline' => array(
            'label' => array('Überschrift für Streifen', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),

        'headline_type' => array(
            'label' => array(
                'de' => array('Typ der Überschrift', ''),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'eval' => array('tl_class' => 'w50'),
        ),


        'onlystyle' => array(
            'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
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

        'image_position' => array(
            'label' => array('Bildposition', 'Nur relevant, wenn "Text & Button untereinander anzeigen" aktiv ist.'),
            'inputType' => 'select',
            'options' => array(
                'above' => 'Bild oberhalb des Teasers anzeigen',
                'below' => 'Bild unterhalb des Teasers anzeigen',
            ),
            'default' => 'above',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'nocolumns',
            ),
        ),

        'image_content' => array(
            'label' => array('Bild', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,webp,svg',
                'tl_class' => 'clr'
            ),
        ),

        'size_right_image' => array(
            'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'clr'
            ),
        ),

        'dynamic_fontsize' => array(
            'label' => array('Schriftgröße abhängig von Bildschirmbreite skalieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'content_css_class' => array(
            'label' => array('CSS-Klasse: Inhalt', 'Zusätzliche CSS-Klasse für den Inhaltscontainer'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
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
);
