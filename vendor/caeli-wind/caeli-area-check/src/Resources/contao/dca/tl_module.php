<?php

use CaeliWind\CaeliAreaCheckBundle\Controller\FrontendModule\AreaCheckMapController;
use CaeliWind\CaeliAreaCheckBundle\Controller\FrontendModule\AreaCheckResultController;

/**
 * Frontend modules
 */
// Map Modul: Mit Weiterleitung zur Result-Seite
$GLOBALS['TL_DCA']['tl_module']['palettes'][AreaCheckMapController::TYPE] = '{title_legend},name,headline,type;{config_legend},jumpTo,allowedCountries;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Result Modul: Mit Weiterleitung zurück zur Map-Seite (optional)
$GLOBALS['TL_DCA']['tl_module']['palettes'][AreaCheckResultController::TYPE] = '{title_legend},name,headline,type;{config_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['jumpTo'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_module']['jumpTo'],
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'        => "int(10) unsigned NOT NULL default '0'",
    'relation'   => ['type' => 'belongsTo', 'load' => 'lazy']
];

$GLOBALS['TL_DCA']['tl_module']['fields']['allowedCountries'] = [
    'label'     => ['Erlaubte Länder', 'Beschränke die Suche auf bestimmte Länder (ISO 3166-1 Alpha-2 Codes wie "de", "gb", "at")'],
    'exclude'   => true,
    'inputType' => 'listWizard',
    'eval'      => [
        'tl_class' => 'w50',
        'helpwizard' => true,
        'allowHtml' => false
    ],
    'sql'       => "blob NULL"
]; 