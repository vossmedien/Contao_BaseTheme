<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Contao\System;

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



        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation (Überschrift)', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true')
        ),



        // NEU: Checkbox für Zweispaltigkeit
        'two_columns' => array(
            'label' => array('Ansprechpartner zweispaltig anzeigen', 'Zeigt die Ansprechpartner nebeneinander in zwei Spalten an (auf größeren Bildschirmen).'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
            'default' => false,
        ),

        // NEU: Select für Ausrichtung der Partner-Boxen
        'partner_alignment' => array(
            'label' => array('Ausrichtung der Partner-Boxen', 'Bestimmt die Textausrichtung innerhalb jeder einzelnen Ansprechpartner-Box.'),
            'inputType' => 'select',
            'options' => array(
                'text-start' => 'Linksbündig (Standard)',
                'text-center' => 'Zentriert',
                'text-end' => 'Rechtsbündig',
            ),
            'default' => 'text-start',
            'eval' => array('tl_class' => 'w50'),
        ),

        // NEU: Select für horizontale Ausrichtung der Reihe
        'row_justify_content' => array(
            'label' => array('Horizontale Ausrichtung der Reihe', 'Bestimmt, wie die Ansprechpartner-Boxen horizontal in der Reihe verteilt werden (justify-content).'),
            'inputType' => 'select',
            'options' => array(
                'justify-content-start'   => 'Links',
                'justify-content-center'  => 'Zentriert (Standard)',
                'justify-content-end'     => 'Rechts',
                'justify-content-between' => 'Gleichmäßiger Abstand (zwischen)',
                'justify-content-around'  => 'Gleichmäßiger Abstand (um)',
            ),
            'default' => 'justify-content-center',
            'eval' => array('tl_class' => 'w50 '),
        ),

        // Steuerung für Bildrundung
        'round_images' => array(
            'label' => array('Bilder abrunden', 'Fügt die Klasse .rounded-circle zu den Bildern hinzu.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' clr'),
            'default' => false,
        ),


        'contact_text' => array(
            'label' => array('Kontakttext', 'Text neben den Ansprechpartnern'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),

        'size' => array(
            'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
            'inputType' => 'imageSize',
            'options' => System::getContainer()->get('contao.image.sizes')->getAllOptions(),
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
            'maxItems' => 6,
            'fields' => array(
                'title' => array(
                    'label' => array('Titel / Abteilung', 'z.B. "Vertrieb" oder "Technik"'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'mandatory' => false),
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
                'email_link' => array(
                    'label' => array('E-Mail-Adresse oder Link', 'z.B. mailto:test@example.com oder /kontakt'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'rgxp' => 'url'),
                ),
                'phone' => array(
                    'label' => array('Telefonnummer', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 5,
                    'eval' => array('tl_class' => 'clr'),
                    'fields' => ButtonHelper::getButtonConfig(),
                ),
                'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true', 'tl_class' => 'w50 clr')
                ),
            ),
        ),
    ),
);