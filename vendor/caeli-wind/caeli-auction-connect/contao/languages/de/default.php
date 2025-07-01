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
 * Content elements
 */
$GLOBALS['TL_LANG']['CTE']['caeli_wind'] = 'Caeli Wind';
$GLOBALS['TL_LANG']['CTE'][AuctionElementController::TYPE] = ['Ausgewählte Auktionen', 'Zeigt ausgewählte Auktionen anhand von IDs an'];

/**
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD']['caeli_wind'] = 'Caeli Wind';
$GLOBALS['TL_LANG']['FMD'][AuctionListingController::TYPE] = ['Auktions-Listing', 'Zeigt eine Liste aller verfügbaren Auktionen an'];
$GLOBALS['TL_LANG']['FMD'][AuctionDetailController::TYPE] = ['Auktions-Detail', 'Zeigt die Details einer Auktion an'];

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_content']['auction_legend'] = 'Auktions-Einstellungen';
$GLOBALS['TL_LANG']['tl_content']['link_legend'] = 'Link-Einstellungen';

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_content']['auction_ids'] = ['Auktions-IDs', 'Geben Sie die IDs der anzuzeigenden Auktionen kommagetrennt ein (z.B. "123,456,789")'];
$GLOBALS['TL_LANG']['tl_content']['jumpTo'] = ['Weiterleitungsseite', 'Wählen Sie die Seite aus, auf der das Detailmodul eingebunden ist'];
$GLOBALS['TL_LANG']['tl_content']['auctionElementFilters'] = ['Zusätzliche Rohdaten-Filter', 'Geben Sie hier Filter für die Rohdaten ein (ein Filter pro Zeile). Format: feldname operator wert (z.B. weaCount = 5 oder status IN STARTED,UPCOMING). Verfügbare Felder und Werte siehe Raw-Data-Preview weiter unten (sobald implementiert).'];
$GLOBALS['TL_LANG']['tl_module']['filter_enabled'] = ['Filter aktivieren', 'Aktiviert die Filterfunktion für Auktionen'];
$GLOBALS['TL_LANG']['tl_module']['filter_bundesland'] = ['Nach Bundesland filtern', 'Zeigt einen Filter für Bundesländer an'];
$GLOBALS['TL_LANG']['tl_module']['filter_landkreis'] = ['Nach Landkreis filtern', 'Zeigt einen Filter für Landkreise an'];
$GLOBALS['TL_LANG']['tl_module']['filter_size'] = ['Nach Flächengröße filtern', 'Zeigt einen Filter für die Flächengröße an'];
$GLOBALS['TL_LANG']['tl_module']['filter_leistung'] = ['Nach Leistung filtern', 'Zeigt einen Filter für die Leistung an'];
$GLOBALS['TL_LANG']['tl_module']['auctionItemTemplate'] = ['Template für Auktions-Eintrag', 'Wählen Sie hier das Template aus, das für die Darstellung jedes einzelnen Auktionseintrags in der Liste verwendet werden soll.'];
$GLOBALS['TL_LANG']['tl_module']['perPageMobile'] = ['Einträge pro Seite (Mobile)', 'Anzahl der Auktionen pro Seite auf mobilen Geräten. Bei 0 wird der Desktop-Wert verwendet.'];

/**
 * Misc
 */
$GLOBALS['TL_LANG']['MSC']['auctionHeaderTitle'] = 'Auktionen';
$GLOBALS['TL_LANG']['MSC']['auctionFilter'] = 'Filter';
$GLOBALS['TL_LANG']['MSC']['auctionFilterApply'] = 'Filter anwenden';
$GLOBALS['TL_LANG']['MSC']['auctionFilterReset'] = 'Zurücksetzen';
$GLOBALS['TL_LANG']['MSC']['auctionNoResults'] = 'Keine Auktionen gefunden';
$GLOBALS['TL_LANG']['MSC']['auctionDetailBack'] = 'Zurück zur Übersicht';
/**
 * Errors
 */
$GLOBALS['TL_LANG']['ERR']['auctionNotFound'] = 'Die gesuchte Auktion wurde nicht gefunden.';
