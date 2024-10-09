<?php

use VSM_HelperFunctions\ButtonHelper;
use VSM_HelperFunctions\GlobalElementConfig;

return array(
    'label' => array('Custom | Ansprechpartner mit Kontaktinformationen', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true')
        ),


        'topline' => array(
            'label' => array('Topline', 'Text oberhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'contact_text' => array(
            'label' => array('Kontakttext', 'Text neben den Ansprechpartnern'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),
        'buttons' => array(
            'label' => array('Buttons', ''),
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 20,
            'eval' => array('tl_class' => 'clr'),
            'fields' => ButtonHelper::getButtonConfig(),
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

        'partners' => array(
            'label' => array('Ansprechpartner', ''),
            'elementLabel' => '%s. Ansprechpartner',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 3,
            'fields' => array(
                'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true')
                ),
                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,webp',
                        'fieldType' => 'radio',
                        'tl_class' => 'clr'
                    ),
                ),
                'name' => array(
                    'label' => array('Name', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'position' => array(
                    'label' => array('Position', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'phone' => array(
                    'label' => array('Telefonnummer', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
        ),
    ),
);