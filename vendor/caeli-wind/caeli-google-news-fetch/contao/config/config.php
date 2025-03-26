<?php

/*
 * This file is part of Caeli Google News Fetcher.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-google-news-fetch
 */

use CaeliWind\CaeliGoogleNewsFetch\Model\CaeliGooglenewsModel;

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['caeli_googlenews'] = [
    'tables' => ['tl_caeli_googlenews']
];

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_caeli_googlenews'] = 'CaeliWind\CaeliGoogleNewsFetch\Model\CaeliGooglenewsModel';

/**
 * Register hooks
 */
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = [\CaeliWind\CaeliGoogleNewsFetch\DataContainer\GoogleNewsFetcher::class, 'onLoadCallback'];
