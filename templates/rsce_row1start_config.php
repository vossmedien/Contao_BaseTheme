<?php
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
return array(
    'label' => array('Spalten/Slider Start-Element', ''),
    'types' => array('content'),
    'contentCategory' => 'Spalten',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'fields' => array(

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

        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
             'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),


        'element_type' => array(
            'label' => array('Darstellungstyp', ''),
            'inputType' => 'radio',
            'options' => array(
                'is_row' => 'Spalten',
                'is_slider' => 'Slider',
            ),
            'default' => 'is_row',
        ),


        'settings_1' => array(
            'label' => array('Einstellungen', ''),
            'inputType' => 'group',
            'eval' => array('tl_class' => 'clr'),
        ),


        'add_mid_element' => array(
            'label' => array('Mittiges Element hinzufügen, liegt über beiden Spalten', 'Funktioniert nur bei 50/50 Spalten und fügt einen mittigen Balken in Body-Background Farbe sowie größere Spaltenabstände hinzu'),
            'inputType' => 'checkbox',
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_row',
            ),
        ),

        'mid_element_content' => array(
            'label' => array('Text für Element', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_row',
            ),
        ),


        'slide_effect' => array(
            'label' => array(
                'de' => array('Slide-Effekt', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'slide' => 'Slide (Standard)',
                'fade' => 'Fade',
                'coverflow' => 'Coverflow',
                'flip' => 'Flip',
                'cube' => 'Cube',
            ),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),

        'space_between' => array(
            'label' => array('Abstand zwischen den Slides in PX', 'Standard: 30'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),

        'slides_per_view' => array(
            'label' => array('Wie viele Slides sind sichtbar', 'Beispielsweise 1.5 um rechts und links eine Vorschau des nächsten Slides anzuzeigen, Standard: auto'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),

        'slides_centered' => array(
            'label' => array('Slides mittig zu einander ausrichten', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),

        'show_pagination' => array(
            'label' => array('Paginierung anzeigen', 'mittig unter dem Slider, in Form von Punkten'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),

        'show_arrows' => array(
            'label' => array('Navigationspfeile anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),

        'loop' => array(
            'label' => array('Automatisch wieder von Anfang starten', '"loop"'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),

        'autoplay' => array(
            'label' => array('Autoplay aktivieren', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),

        'autoplay_time' => array(
            'label' => array('Autoplay-Zyklus', 'nach wie viel MS soll zum nächsten Slide gewechselt werden, Standard: 3000'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'autoplay',
            ),
        ),


        'transition_time' => array(
            'label' => array('Animationszeit in ms', 'Standard: 500'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'element_type',
                'value' => 'is_slider',
            ),
        ),


    ),
    'wrapper' => array(
        'type' => 'start',
    ),
);
