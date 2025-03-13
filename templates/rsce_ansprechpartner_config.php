<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

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

        'contacts' => array(
            'label' => array('Kontakte', ''),
            'elementLabel' => '%s. Kontakt',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 10,
            'fields' => array(
                'title' => array(
                    'label' => array('Titel', 'z.B. "Landowners" oder "Project Developers"'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'mandatory' => true),
                ),
                'image' => array(
                    'label' => array('Profilbild', ''),
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
                    'eval' => array('tl_class' => 'w50', 'mandatory' => true),
                ),
                'email_link' => array(
                    'label' => array('E-Mail-Link', 'Link zum Kontaktformular oder mailto: Link'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'rgxp' => 'url'),
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