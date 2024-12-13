<?php

// contao/dca/tl_calendar_events.php

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['isSoldOut'] = [
    'label' => ['Ausgebucht', 'Veranstaltung ist ausgebucht'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventTime'] = [
    'label' => ['Veranstaltungsbeginn', 'Uhrzeit der Veranstaltung'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'time', 'datepicker' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(10) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['priceAdvance'] = [
    'label' => ['Vorverkaufspreis', 'Preis im Vorverkauf'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 10, 'tl_class' => 'w50', 'rgxp' => 'digit'],
    'sql' => "varchar(10) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['priceEvening'] = [
    'label' => ['Abendkassenpreis', 'Preis an der Abendkasse'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 10, 'tl_class' => 'w50', 'rgxp' => 'digit'],
    'sql' => "varchar(10) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['ticketUrl'] = [
    'label' => ['Ticket-Link', 'URL zum Ticketverkauf'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['additionalUrls'] = [
    'label' => ['Zusätzliche URLs', 'Weitere relevante Links'],
    'exclude' => true,
    'inputType' => 'multiColumnWizard',
    'eval' => [
        'columnFields' => [
            'url' => [
                'label' => ['URL'],
                'inputType' => 'text',
                'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'tl_class' => 'w50']
            ],
            'title' => [
                'label' => ['Bezeichnung'],
                'inputType' => 'text',
                'eval' => ['tl_class' => 'w50']
            ]
        ],
        'tl_class' => 'clr'
    ],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['headerImage'] = [
    'label' => ['Kopfbild', 'Wählen Sie ein Kopfbild aus'],
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => [
        'filesOnly' => true,
        'fieldType' => 'radio',
        'multiple' => false,
        'extensions' => 'jpg,jpeg,png,svg,webp',
        'tl_class' => 'clr'
    ],
    'sql' => "binary(16) NULL"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['headerImageSize'] = [
    'label' => ['Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'],
    'inputType' => 'imageSize',
    'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'eval' => [
        'rgxp' => 'digit',
        'includeBlankOption' => true,
        'tl_class' => 'clr'
    ],
    'sql' => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['longDescription'] = [
    'label' => ['Langbeschreibung', 'Ausführliche Beschreibung der Veranstaltung'],
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => [
        'rte' => 'tinyMCE',
        'tl_class' => 'clr',
        'helpwizard' => true
    ],
    'explanation' => 'insertTags',
    'sql' => "mediumtext NULL"
];

// Palette
use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    // Veranstaltungsbeginn zur date_legend hinzufügen
    ->addField('eventTime', 'date_legend', PaletteManipulator::POSITION_APPEND)

    // Neue Legenden und zugehörige Felder
    ->addLegend('price_legend', 'details_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['priceAdvance', 'priceEvening', 'isSoldOut'], 'price_legend', PaletteManipulator::POSITION_APPEND)
    ->addLegend('link_legend', 'price_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['ticketUrl', 'additionalUrls'], 'link_legend', PaletteManipulator::POSITION_APPEND)

    // Langbeschreibung zu den Eventdetails
    ->addField('longDescription', 'address', PaletteManipulator::POSITION_AFTER)

    // Kopfbild
    ->addField(['headerImage', 'headerImageSize'], 'addImage')
    ->applyToPalette('default', 'tl_calendar_events');