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

/**
 * Frontend modules
 */
// Listing Modul: Mit Weiterleitung zur Detail-Seite
$GLOBALS['TL_DCA']['tl_module']['palettes'][AuctionListingController::TYPE] = '{title_legend},name,headline,type;{config_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Detail Modul: Mit Weiterleitung zurück zur Listing-Seite
$GLOBALS['TL_DCA']['tl_module']['palettes'][AuctionDetailController::TYPE] = '{title_legend},name,headline,type;{config_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Filter Modul: Ohne spezielle Weiterleitung
$GLOBALS['TL_DCA']['tl_module']['palettes'][AuctionFilterController::TYPE] = '{title_legend},name,headline,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
