<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

//rsce_my_element_config.php
return array(
    'label' => array('Custom | Bilder-Galerie (slider)', ''),
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
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
        ),

        'slide_animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation der Slides', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
        ),


        'deactivate_slider' => array(
            'label' => array('Slider deaktivieren auf...', 'Ansicht wird auf "masonry" umgestellt'),
            'inputType' => 'select',
            'options' => array(
                '' => 'Nie',
                'desktop' => 'Desktop',
                'mobile' => 'Mobile',
                'both' => 'Desktop und Mobile',
            ),
            'eval' => array('tl_class' => 'clr'),
        ),

        'settings_masonry' => array(
            'label' => array('Masonry-Einstellungen', ''),
            'inputType' => 'group',

            'dependsOn' => array(
                'field' => 'deactivate_slider',
                'value' => array('desktop', 'mobile', 'both'),
            ),
        ),

        'masonry_spalten' => array(
            'label' => array('Wie viele Spalten soll das Masonry-Grid standardmäßig haben', 'Standard: 3'),
            'inputType' => 'text',
            'eval' => array(
                'tl_class' => 'w50',
                'rgxp' => 'digit',
                'maxlength' => 2
            ),
        ),
        'masonry_spalten_mobile' => array(
            'label' => array('Wie viele Spalten soll das Masonry-Grid auf mobile standardmäßig haben', 'Standard: 2'),
            'inputType' => 'text',
            'eval' => array(
                'tl_class' => 'w50',
                'rgxp' => 'digit',
                'maxlength' => 2
            ),
        ),


        'limit_display' => array(
            'label' => array('Darstellung begrenzen', 'Nur eine bestimmte Anzahl von Zeilen anzeigen'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 m12'),
            'dependsOn' => array(
                'field' => 'deactivate_slider',
                'value' => array('desktop', 'mobile', 'both'),
            ),
        ),
        'visible_rows' => array(
            'label' => array('Anzahl sichtbarer Zeilen', 'Wie viele Zeilen sollen initial sichtbar sein?'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'natural'),
            'dependsOn' => array(
                'field' => 'limit_display',
            ),
        ),

        'settings_design' => array(
            'label' => array('Darstellungs-Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
        ),


        'selecttype' => array(
            'label' => array('Bilder ', ''),
            'inputType' => 'radio',
            'eval' => array('tl_class' => 'clr'),
            'options' => array(
                'multiple' => 'Mehrere Bilder oder Ordner auswählen',
                'single' => 'Bilder einzeln auswählen und optional Bildbeschreibung und Bildtitel hinzufügen',
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

            'dependsOn' => array(
                'field' => 'selecttype',
                'value' => 'multiple',
            ),
        ),


        'settings_1' => array(
            'label' => array('Slider-Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
        ),


        'space_between' => array(
            'label' => array('Abstand zwischen den Slides in PX', 'Standard: 30'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'slides_per_view' => array(
            'label' => array('Wie viele Slides sind sichtbar', 'Beispielsweise 1.5 um rechts und links eine Vorschau des nächsten Slides anzuzeigen'),
            'inputType' => 'text',
            'eval' => array(
                'tl_class' => 'w50', // Erlaubt nur Zahlen// Begrenzt die Eingabe auf maximal 2 Ziffern
            ),
        ),

        'slides_per_view_mobile' => array(
            'label' => array('Wie viele Slides sind sichtbar (mobile)', 'Standard: 1'),
            'inputType' => 'text',
            'eval' => array(
                'tl_class' => 'w50',
                // Erlaubt nur Zahlen// Begrenzt die Eingabe auf maximal 2 Ziffern
            ),
        ),


        'slide_effect' => array(
            'label' => array(
                'de' => array('Slide-Effekt', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'slide' => 'Slide (Standard)',
                'coverflow' => 'Coverflow',
                'fade' => 'Fade',
                'flip' => 'Flip',
                'cube' => 'Cube',

            ),
            'eval' => array('tl_class' => 'w50'),
        ),


        'transition_time' => array(
            'label' => array('Animationszeit in ms', 'Standard: 1500'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'open_lightbox' => array(
            'label' => array('Bilder in Lightbox öffnen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'show_pagination' => array(
            'label' => array('Paginierung anzeigen', 'mittig unter dem Slider, in Form von Punkten'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'show_arrows' => array(
            'label' => array('Pfeile anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),


        'loop' => array(
            'label' => array('Automatisch wieder von Anfang starten', '"loop"'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'autoplay' => array(
            'label' => array('Autoplay aktivieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
        ),

        'autoplay_time' => array(
            'label' => array('Autoplay-Zyklus', 'nach wie viel MS soll zum nächsten Slide gewechselt werden, Standard: 3000'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'autoplay',
            ),
        ),

        'settings_2' => array(
            'label' => array('Slides', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'selecttype',
                'value' => 'multiple',
            ),
        ),


        'multiSRC' => array(
            'inputType' => 'standardField',

            'eval' => array(
                'multiple' => true,
                'fieldType' => 'checkbox',
                'orderField' => 'orderSRC',
                'files' => true,
                'mandatory' => false,
                'isGallery' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
            ),
        ),


        'galery' => array(
            'label' => array('Slides', ''),
            'elementLabel' => '%s. Slide',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 20,
            'dependsOn' => array(
                'field' => 'selecttype',
                'value' => 'single',
            ),
            'fields' => array(
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

                'slide' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                        'tl_class' => 'clr'
                    ),
                ),
                'slide_text' => array(
                    'label' => array('Beschreibung', ''),
                    'inputType' => 'text',
                ),
            ),
        ),
    ),
);
