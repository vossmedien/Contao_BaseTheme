<?php

use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

$config = array(
    'label' => array('Custom | Kursplan mit Filter (course_schedule)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'animation_type' => array(
            'label' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr')
        ),
        'courses' => array(
            'label' => array('Kurse', ''),
            'elementLabel' => '%s. Kurs',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 50,
            'fields' => array(
                'category' => array(
                    'label' => array('Kategorie', ''),
                    'inputType' => 'select',
                    'options' => array(
                        'minis' => 'Minis (3-5 Jahre)',
                        'kids_6_9' => 'Kids (6-9 Jahre)',
                        'teens' => 'Teenager (10-14 Jahre)',
                        'adults' => 'Erwachsene',
                        'seniors' => '50 PLUS',
                        'fitness' => 'FitnessBoxen'
                    ),
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'weekday' => array(
                    'label' => array('Wochentag', ''),
                    'inputType' => 'select',
                    'options' => array(
                       // 'monday' => 'Montag',
                        'tuesday' => 'Dienstag',
                        'wednesday' => 'Mittwoch',
                        'thursday' => 'Donnerstag',
                        'friday' => 'Freitag',
                        //'saturday' => 'Samstag',
                        //'sunday' => 'Sonntag'
                    ),
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'time' => array(
                    'label' => array('Uhrzeit', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'additional_info' => array(
                    'label' => array('Zusätzliche Information', 'Optional'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);