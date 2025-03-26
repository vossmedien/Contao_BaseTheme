<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Google News Fetcher.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-google-news-fetch
 */

namespace CaeliWind\CaeliGoogleNewsFetch\Model;

use Contao\Model;

/**
 * Modell für die Google News Konfiguration
 */
class CaeliGooglenewsModel extends Model
{
    /**
     * Tabellenname
     * @var string
     */
    protected static $strTable = 'tl_caeli_googlenews';
    
    /**
     * Findet ein Modell anhand seiner ID
     */
    public static function findById($intId, array $arrOptions = [])
    {
        return static::findByPk($intId, $arrOptions);
    }
}
