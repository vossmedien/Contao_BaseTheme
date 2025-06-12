<?php


use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

$config = array(
    'label' => array('Custom | Terminliste (event_list)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'none',
    ),
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
            'label' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),
        'events' => array(
            'label' => array('Termine', ''),
            'elementLabel' => '%s. Termin',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 20,
            'fields' => array(
                'title' => array(
                    'label' => array('Titel', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'description' => array(
                    'label' => array('Beschreibung', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),
                'start_time' => array(
                    'label' => array('Startzeit', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'rgxp' => 'time', 'tl_class' => 'w50'),
                ),
                'end_time' => array(
                    'label' => array('Endzeit', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'rgxp' => 'time', 'tl_class' => 'w50'),
                ),
                'location' => array(
                    'label' => array('Veranstaltungsort', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'street' => array(
                    'label' => array('Straße und Hausnummer', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'postal_code' => array(
                    'label' => array('Postleitzahl', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'city' => array(
                    'label' => array('Stadt', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'website' => array(
                    'label' => array('Website', ''),
                    'inputType' => 'url',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);