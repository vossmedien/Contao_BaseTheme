<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// NEUE Event-Felder für caeli-wind/caeli-news-event-enhancements
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['onSiteAppointmentLink'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['onSiteAppointmentLink'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['workshopBookingLink'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['workshopBookingLink'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['learnMoreLink'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['learnMoreLink'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['webinarRegistrationLink'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['webinarRegistrationLink'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['targetAudience'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['targetAudience'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventStatus'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['eventStatus'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
]; 

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventDates'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['eventDates'],
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr',
        'rte' => 'tinyMCE'
    ],
    'sql' => "text NULL"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['detailTitleEvents'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['detailTitleEvents'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['webinarShortDescription'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['webinarShortDescription'],
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
    'sql' => "text NULL"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['webinarContactName'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['webinarContactName'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['webinarContactTitle'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['webinarContactTitle'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['webinarContactImage'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['webinarContactImage'],
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => [
        'filesOnly' => true,
        'fieldType' => 'radio',
        'extensions' => Contao\Config::get('validImageTypes'),
        'tl_class' => 'clr'
    ],
    'sql' => "binary(16) NULL"
];

// Palettenanpassung für die neuen Event-Felder im caeli-wind Bundle
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    // Neue Legende für externe Links nach der Standard date_legend
    ->addLegend('external_links_legend', 'date_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(['onSiteAppointmentLink', 'workshopBookingLink', 'learnMoreLink', 'webinarRegistrationLink'], 'external_links_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)

    // Neue Legende für zusätzliche Eventinformationen nach der external_links_legend
    ->addLegend('additional_event_info_legend', 'external_links_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(['targetAudience', 'eventStatus', 'detailTitleEvents'], 'additional_event_info_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->addField('eventDates', 'additional_event_info_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)

    // Neue Legende für Webinar-Kontaktinformationen nach der additional_event_info_legend
    ->addLegend('webinar_contact_legend', 'additional_event_info_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(['webinarShortDescription', 'webinarContactName', 'webinarContactTitle', 'webinarContactImage'], 'webinar_contact_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)

    ->applyToPalette('default', 'tl_calendar_events');
