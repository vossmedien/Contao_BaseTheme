<?php

declare(strict_types=1);

// Inizialisiere die Legenden, falls sie noch nicht existieren
if (!isset($GLOBALS['TL_DCA']['tl_page']['legends'])) {
    $GLOBALS['TL_DCA']['tl_page']['legends'] = [];
}

// Legends definieren
$GLOBALS['TL_DCA']['tl_page']['legends']['pin_protected_legend'] = 'PIN-Schutz Einstellungen';

// Paletten erweitern - hier fügen wir alle Felder direkt in die Palette ein
$GLOBALS['TL_DCA']['tl_page']['palettes']['regular'] = str_replace(
    '{protected_legend',
    '{pin_protected_legend},pin_protected,pin_value,pin_login_page,pin_timeout;{protected_legend',
    $GLOBALS['TL_DCA']['tl_page']['palettes']['regular']
);

// Felder hinzufügen
$GLOBALS['TL_DCA']['tl_page']['fields']['pin_protected'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class' => 'w50 clr'], // Kein submitOnChange mehr
    'sql'                     => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_page']['fields']['pin_value'] = [
    'exclude'                 => true,
    'search'                  => true,
    'inputType'               => 'text',
    'eval'                    => [
        'maxlength' => 255,
        'tl_class' => 'w50',
        'mandatory' => false // Nicht mandatory, damit es auch ohne Pin gespeichert werden kann
    ],
    'sql'                     => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_page']['fields']['pin_login_page'] = [
    'exclude'                 => true,
    'inputType'               => 'pageTree',
    'foreignKey'              => 'tl_page.title',
    'eval'                    => [
        'fieldType' => 'radio',
        'tl_class' => 'w50',
        'mandatory' => false // Nicht mandatory, damit es auch ohne Pin gespeichert werden kann
    ],
    'sql'                     => "int(10) unsigned NOT NULL default 0",
    'relation'                => ['type' => 'hasOne', 'load' => 'lazy']
];

$GLOBALS['TL_DCA']['tl_page']['fields']['pin_timeout'] = [
    'exclude'                 => true,
    'inputType'               => 'text',
    'default'                 => 1800, // 30 Minuten
    'eval'                    => [
        'rgxp' => 'natural',
        'tl_class' => 'w50'
    ],
    'sql'                     => "int(10) unsigned NOT NULL default 1800"
]; 
