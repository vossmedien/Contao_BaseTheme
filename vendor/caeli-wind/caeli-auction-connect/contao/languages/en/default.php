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
use CaeliWind\CaeliAuctionConnect\Controller\FrontendModule\AuctionDetailController;
use CaeliWind\CaeliAuctionConnect\Controller\FrontendModule\AuctionListingController;

/**
 * Content element
 */
$GLOBALS['TL_LANG']['CTE']['caeli_wind'] = 'Caeli Wind';
$GLOBALS['TL_LANG']['CTE'][AuctionElementController::TYPE] = ['Selected Auctions', 'Displays selected auctions based on IDs'];

/**
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD']['caeli_wind'] = 'Caeli Wind';
$GLOBALS['TL_LANG']['FMD'][AuctionListingController::TYPE] = ['Auction Listing', 'Shows a list of all available auctions'];
$GLOBALS['TL_LANG']['FMD'][AuctionDetailController::TYPE] = ['Auction Detail', 'Shows the details of an auction'];

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_content']['auction_legend'] = 'Auction Settings';
$GLOBALS['TL_LANG']['tl_content']['link_legend'] = 'Link Settings';

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_content']['auction_ids'] = ['Auction IDs', 'Enter the IDs of auctions to display, separated by commas (e.g. "123,456,789")'];
$GLOBALS['TL_LANG']['tl_content']['jumpTo'] = ['Redirect page', 'Select the page where the detail module is included'];
$GLOBALS['TL_LANG']['tl_module']['filter_enabled'] = ['Enable filters', 'Enables filtering functionality for auctions'];
$GLOBALS['TL_LANG']['tl_module']['filter_bundesland'] = ['Filter by state', 'Shows a filter for states'];
$GLOBALS['TL_LANG']['tl_module']['filter_landkreis'] = ['Filter by district', 'Shows a filter for districts'];
$GLOBALS['TL_LANG']['tl_module']['filter_size'] = ['Filter by area size', 'Shows a filter for area size'];
$GLOBALS['TL_LANG']['tl_module']['filter_leistung'] = ['Filter by power', 'Shows a filter for power'];

/**
 * Miscellaneous
 */
$GLOBALS['TL_LANG']['MSC']['auctionHeaderTitle'] = 'Auctions';
$GLOBALS['TL_LANG']['MSC']['auctionFilter'] = 'Filter';
$GLOBALS['TL_LANG']['MSC']['auctionFilterApply'] = 'Apply Filter';
$GLOBALS['TL_LANG']['MSC']['auctionFilterReset'] = 'Reset';
$GLOBALS['TL_LANG']['MSC']['auctionNoResults'] = 'No auctions found';
$GLOBALS['TL_LANG']['MSC']['auctionDetailBack'] = 'Back to overview';

/**
 * Errors
 */
$GLOBALS['TL_LANG']['ERR']['auctionNotFound'] = 'The requested auction could not be found.';
