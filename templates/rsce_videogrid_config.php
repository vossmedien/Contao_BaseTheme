<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

$config = array(
    'label' => array('Custom | Video Grid', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'standardFields' => array('headline', 'cssID'),
    'moduleCategory' => 'miscellaneous',
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
        // Layout-Einstellungen
        'grid_settings' => array(
            'label' => array('Grid-Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
        ),
        'min_video_width' => array(
            'label' => array('Minimale Video-Breite (px)', 'Standard: 300'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
        ),
        'grid_gap' => array(
            'label' => array('Abstand zwischen Videos (px)', 'Standard: 20'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
        ),
        'columns_xl' => array(
            'label' => array('Spalten XL (≥1400px)', 'Standard: 4'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
        ),
        'columns_lg' => array(
            'label' => array('Spalten LG (≥1200px)', 'Standard: 3'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
        ),
        'columns_md' => array(
            'label' => array('Spalten MD (≥992px)', 'Standard: 2'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
        ),
        'columns_sm' => array(
            'label' => array('Spalten SM (≥768px)', 'Standard: 2'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
        ),
        'columns_xs' => array(
            'label' => array('Spalten XS (<768px)', 'Standard: 1'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
        ),

         'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
             'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),


        // Video-Liste
        'videos' => array(
            'label' => array('Videos', ''),
            'elementLabel' => '%s. Video',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(
                'preview_video' => array(
                    'label' => array('Vorschau-Video', 'Kurze Version für Auto-Play'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'mp4,webm',
                        'mandatory' => true,
                        'tl_class' => 'clr'
                    ),
                ),
                'main_video' => array(
                    'label' => array('Hauptvideo', 'Wird in Lightbox geöffnet'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'mp4,webm',
                        'mandatory' => true,
                        'tl_class' => 'clr'
                    ),
                ),
                'poster_image' => array(
                    'label' => array('Poster-Bild', 'Wird angezeigt bis das Video geladen ist'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,webp',
                        'tl_class' => 'clr'
                    ),
                ),
                'title' => array(
                    'label' => array('Titel', 'Optional'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'description' => array(
                    'label' => array('Beschreibung', 'Optional'),
                    'inputType' => 'textarea',
                    'eval' => array('tl_class' => 'clr', 'rte' => 'tinyMCE'),
                ),
            ),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);