<?php

use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;

//rsce_process_steps_config.php
$config = array(
    'label' => array('Custom | Prozess-Schritte (process_steps)', 'Stellt einen Prozess in nummerierten Schritten mit Bild dar.'),
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
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ), 'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'intro_text' => array(
            'label' => array('Einleitungstext', 'Wird über den Prozess-Schritten angezeigt'),
            'inputType' => 'textarea',
            'eval' => array('tl_class' => 'clr', 'rte' => 'tinyMCE'),
        ),
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation (Gesamtes Element)', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),

         'container_css_class' => array(
            'label' => array('Container CSS-Klasse', 'Optionale CSS-Klasse für einen inneren Wrapper um den gesamten Inhalt des Elements.'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),


        'rows' => array(
            'label' => array('Prozess-Schritte', ''),
            'elementLabel' => '%s. Schritt',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(

                'step_animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation (Dieser Schritt)', 'Siehe https://animate.style/ für Beispiele. Überschreibt globale Einstellung nicht, wird zusätzlich angewendet.'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true', 'includeBlankOption' => true)
                ),

                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => Contao\Config::get('validImageTypes'),
                        'tl_class' => 'w50',
                    ),
                ),
                'image_no_lazy' => array(
                    'label' => array('Bild ohne Lazy-Loading laden', 'Deaktiviert das Lazy-Loading für das Bild'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'size' => array(
                    'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
                    'inputType' => 'imageSize',
                    'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                    ),
                ),

                'headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50 clr'),
                ),

                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', ''),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getHeadlineTagOptions(),
                    'eval' => array('tl_class' => 'w50'),
                    'default' => 'h3',
                ),

                'onlystyle' => array(
                    'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),

                'description' => array(
                    'label' => array('Beschreibung', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),
            ),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config); 