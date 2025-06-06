<?php

use CaeliWind\CaeliAreaCheckBundle\Controller\FrontendModule\AreaCheckMapController;
use CaeliWind\CaeliAreaCheckBundle\Controller\FrontendModule\AreaCheckResultController;

/**
 * Frontend modules
 */
// Map Modul: Mit Weiterleitung zur Result-Seite
$GLOBALS['TL_DCA']['tl_module']['palettes'][AreaCheckMapController::TYPE] = '{title_legend},name,headline,type;{config_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

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