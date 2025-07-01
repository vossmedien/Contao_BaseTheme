<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Auction Connect.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-auction-connect
 */

use CaeliWind\CaeliAuctionConnect\Controller\FrontendModule\AuctionListingController;
use CaeliWind\CaeliAuctionConnect\Controller\FrontendModule\AuctionDetailController;
use CaeliWind\CaeliAuctionConnect\Controller\FrontendModule\AuctionFilterController;
// Verwendung des DcaHelpers für den Callback
use CaeliWind\CaeliAuctionConnect\Dca\ModuleDcaHelper;

/**
 * Frontend modules
 */
// Listing Modul: Mit Weiterleitung zur Detail-Seite
$GLOBALS['TL_DCA']['tl_module']['palettes'][AuctionListingController::TYPE] = '{title_legend},name,headline,type;{config_legend},jumpTo,auctionItemTemplate,perPage,perPageMobile;{api_legend},auctionApiUrlParams;{filter_legend},auctionListingFilters,auctionRawDataPreviewMod;{sort_legend},auctionSortRules;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Detail Modul: Mit Weiterleitung zurück zur Listing-Seite
$GLOBALS['TL_DCA']['tl_module']['palettes'][AuctionDetailController::TYPE] = '{title_legend},name,headline,type;{config_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Filter Modul: Mit Vorschau und Filter-Optionen
$GLOBALS['TL_DCA']['tl_module']['palettes'][AuctionFilterController::TYPE] = '{title_legend},name,headline,type;{config_legend},auctionFilterOptions,auctionRawDataPreviewMod;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Definition des neuen Feldes
$GLOBALS['TL_DCA']['tl_module']['fields']['auctionItemTemplate'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['auctionItemTemplate'],
    'inputType' => 'select',
    'options_callback' => [ModuleDcaHelper::class, 'getAuctionItemTemplates'],
    'eval'      => ['chosen' => true, 'tl_class' => 'w50', 'includeBlankOption' => true],
    'sql'       => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['auctionListingFilters'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['auctionListingFilters'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:60px', 'preserveTags' => true, 'rte' => false, 'tl_class' => 'clr'],
    'sql'       => "text NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['auctionSortRules'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['auctionSortRules'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:60px', 'preserveTags' => true, 'rte' => false, 'tl_class' => 'clr'],
    'sql'       => "text NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['auctionRawDataPreviewMod'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['auctionRawDataPreviewMod_label'],
    'exclude'   => true,
    'inputType' => 'text', // Wird durch Callback gerendert
    'eval'      => ['tl_class' => 'clr w50', 'allowHtml' => true, 'doNotSave' => true],
    'input_field_callback' => [ModuleDcaHelper::class, 'displayAuctionRawDataPreviewMod'],
];

// Alte Sortierfelder für Abwärtskompatibilität beibehalten
$GLOBALS['TL_DCA']['tl_module']['fields']['auctionSortBy'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['auctionSortBy'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['leistung_mw', 'flaeche_ha', 'countDown', 'internalRateOfReturnBeforeRent'], 
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['auctionSortBy_options'],
    'eval'      => ['chosen' => true, 'tl_class' => 'w50', 'includeBlankOption' => true],
    'sql'       => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['auctionSortDirection'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['auctionSortDirection'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['asc' => 'Aufsteigend', 'desc' => 'Absteigend'],
    'default'   => 'asc',
    'eval'      => ['chosen' => true, 'tl_class' => 'w50'],
    'sql'       => "varchar(4) NOT NULL default 'asc'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['perPage'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['perPage'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql'       => "smallint(5) unsigned NOT NULL default '12'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['perPageMobile'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['perPageMobile'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql'       => "smallint(5) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['auctionFilterOptions'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['auctionFilterOptions'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:60px', 'preserveTags' => true, 'rte' => false, 'tl_class' => 'clr'],
    'sql'       => "text NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['auctionApiUrlParams'] = [
    'label'     => ['API URL Parameter', 'Zusätzliche Parameter für die API-URL (z.B. "/closed?language=de")'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''"
];
