<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
//rsce_my_element_config.php
$config = array(
    'label' => array('Custom | Anker-Navigation (onepagenavi)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
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
                    'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
                ),


        'nav_style' => array(
            'label' => array(
                'de' => array('Text-Darstellungstyp', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                'style-1' => 'Style 1: Navigation erscheint vertikal nach dem scrollen und ist fixiert',
                'style-2' => 'Style 2: Navigation befindet sich horizontal innerhalb eines Artikels und scrollt nach Berührung mit',
            ),
            'eval' => array('tl_class' => 'clr'),
        ),

        'background_color' => array(
            'label' => array('Hintergrundfarbe', 'Standard: Hauptfarbe'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'text_color' => array(
            'label' => array('Schriftfarbe', 'Standard: Weiß'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),


        'offset' => array(
            'label' => array('Wie viel PX soll gescrollt werden,  bis die Navigation sichtbar wird', 'Standard: 300'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'nav_style',
                'value' => 'style-1',
            ),
            'eval' => array('tl_class' => 'clr'),
        ),


        'smaller_containers' => array(
            'label' => array('"Container" auf der Seite schmäler machen, damit OnepageNavi den Content nicht überlagert', 'Greift nicht bei Elementen die auf die volle Breite gehen'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'nav_style',
                'value' => 'style-1',
            ),
        ),


        'add_totopbutton' => array(
            'label' => array('"Nach oben"-Button hinzufügen', 'Bei Style-2 wird der Button nur Mobile ein- bzw. ausgeblendet'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'hide_mobile' => array(
            'label' => array('Auf dem Handy ausblenden', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'urls' => array(
            'label' => array('Link', ''),
            'elementLabel' => '%s. Link',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 999,
            'eval' => array('tl_class' => 'clr'),
            'fields' => array(
                'text' => array(
                    'label' => array('Bezeichnung', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'link' => array(
                    'label' => array('Link', 'Anker-ID eingeben, z. B. "#anker"'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);