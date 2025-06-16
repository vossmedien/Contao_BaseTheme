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

use CaeliWind\CaeliAuctionConnect\Controller\ContentElement\AuctionElementController;

/**
 * Content elements
 */
$GLOBALS['TL_DCA']['tl_content']['palettes'][AuctionElementController::TYPE] = '
    {type_legend},type,headline;
    {auction_legend},auctionElementFilters,auctionElementLimit,auctionRawDataPreview;
    {api_legend},auctionApiUrlParamsCE;
    {sort_legend_ce},auctionSortRulesCE;
    {link_legend:hide},jumpTo;
    {template_legend:hide},customTpl,auctionItemTemplateCE;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID;
    {invisible_legend:hide},invisible,start,stop
';

/**
 * Felder
 */
// Das Feld auction_ids wurde entfernt. Die Auswahl erfolgt nun über auctionElementFilters.
// $GLOBALS['TL_DCA']['tl_content']['fields']['auction_ids'] = [
//     'exclude'                 => true,
//     'inputType'               => 'text',
//     'eval'                    => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'clr'],
//     'sql'                     => "varchar(255) NOT NULL default ''"
// ];

$GLOBALS['TL_DCA']['tl_content']['fields']['auctionElementFilters'] = [
    'exclude'                 => true,
    'inputType'               => 'textarea',
    'eval'                    => ['style' => 'height:60px', 'preserveTags' => true, 'rte' => false, 'tl_class' => 'clr'],
    'sql'                     => "text NULL"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['auctionElementLimit'] = [
    'label'     => ['Anzahl begrenzen', 'Maximale Anzahl der angezeigten Auktionen (0 = unbegrenzt)'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql'       => "smallint(5) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['auctionRawDataPreview'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['auctionRawDataPreview_label'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'clr w50', 'allowHtml' => true, 'doNotSave' => true],
    'input_field_callback' => ['CaeliWind\CaeliAuctionConnect\Dca\ContentDcaHelper', 'displayAuctionRawDataPreview'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['auctionSortRulesCE'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['auctionSortRulesCE'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['style' => 'height:60px', 'preserveTags' => true, 'rte' => false, 'tl_class' => 'clr'],
    'sql'       => "text NULL"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['auctionItemTemplateCE'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['auctionItemTemplateCE'],
    'exclude'   => true,
    'inputType' => 'select',
    'options_callback' => ['CaeliWind\CaeliAuctionConnect\Dca\ModuleDcaHelper', 'getAuctionItemTemplates'],
    'eval'      => ['chosen' => true, 'tl_class' => 'w50', 'includeBlankOption' => true],
    'sql'       => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['jumpTo'] = [
    'exclude'                 => true,
    'inputType'               => 'pageTree',
    'foreignKey'              => 'tl_page.title',
    'eval'                    => ['fieldType'=>'radio', 'tl_class'=>'clr'],
    'sql'                     => "int(10) unsigned NOT NULL default 0",
    'relation'                => ['type'=>'hasOne', 'load'=>'lazy']
];

$GLOBALS['TL_DCA']['tl_content']['fields']['auctionApiUrlParamsCE'] = [
    'label'     => ['API URL Parameter', 'Zusätzliche Parameter für die API-URL (z.B. "/closed?language=de")'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''"
];
