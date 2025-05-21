<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

//rsce_my_element_config.php
return array(
    'label' => array('Custom | Timeline (timeline)', ''),
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
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),


        'linecolor' => array(
            'label' => array('Linienfarbe', 'Standard: Sekundärfarbe'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50')
        ),

        'dotcolor' => array(
            'label' => array('Punktfarbe', 'Standard: Hauptfarbe'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50')
        ),


        'rows' => array(
            'label' => array('Ereignisse', ''),
            'elementLabel' => '%s. Ereignis',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'fields' => array(

                'settings_1' => array(
                    'label' => array('Einstellungen', ''),
                    'inputType' => 'group',
                    'dependsOn' => array(
                        'field' => 'is_slider',
                    ),
                    'eval' => array('collapsible' => true, 'collapsed' => true),
                ),

                'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true')
                ),

                'backgroundcolor' => array(
                    'label' => array('Hintergrundfarbe', 'Standard: hellgrau'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50')
                ),

                'textcolor' => array(
                    'label' => array('Schriftfarbe', 'Standard: Body-Textfarbe'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50')
                ),


                'settings_2' => array(
                    'label' => array('Listendarstellung', ''),
                    'inputType' => 'group',
                    'dependsOn' => array(
                        'field' => 'is_slider',
                    ),
                ),


                'step' => array(
                    'label' => array('Ereignistitel', 'z. B. 1999 o. ä.'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'clr')
                ),

                'icon' => array(
                    'label' => array('Icon', 'innerhalb einer Bubble neben dem Ereignistitel'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    )
                ),


                'settings_3' => array(
                    'label' => array('Inhalt', ''),
                    'inputType' => 'group',
                    'dependsOn' => array(
                        'field' => 'is_slider',
                    ),
                ),


                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    )
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


                'headline' => array(
                    'label' => array('Überschrift', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),

                'headline_type' => array(
                    'label' => array(
                        'de' => array('Typ der Überschrift', 'für linke Spalte'),
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

                'description' => array(
                    'label' => array('Beschreibung', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE'),
                ),


                'add_buttons' => array(
                    'label' => array('Buttons hinzufügen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                    'options' => array(
                        '1' => 'Buttons hinzufügen',
                    ),
                ),


                'button_textalign' => array(
                    'label' => array(
                        'de' => array('Button - Ausrichtung', ''),
                    ),
                    'inputType' => 'select',
                    'options' => array(
                        'text - start' => 'Linksbündig',
                        'text - center' => 'Zentriert',
                        'text - end' => 'Rechtsbündig',
                    ),

                    'dependsOn' => array(
                        'field' => 'add_buttons',
                        'value' => '1',
                    ),
                ),


                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 20,
                    'eval' => array('tl_class' => 'clr'),
                    'dependsOn' => array(
                        'field' => 'add_buttons',
                        'value' => '1',
                    ),
                    'fields' => ButtonHelper::getButtonConfig(),
                ),
            ),
        ),
    ),
);
