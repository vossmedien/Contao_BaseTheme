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
    {auction_legend},auction_ids;
    {link_legend:hide},jumpTo;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID;
    {invisible_legend:hide},invisible,start,stop
';

/**
 * Felder
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['auction_ids'] = [
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'clr'],
    'sql'                     => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['jumpTo'] = [
    'exclude'                 => true,
    'inputType'               => 'pageTree',
    'foreignKey'              => 'tl_page.title',
    'eval'                    => ['fieldType'=>'radio', 'tl_class'=>'clr'],
    'sql'                     => "int(10) unsigned NOT NULL default 0",
    'relation'                => ['type'=>'hasOne', 'load'=>'lazy']
];
