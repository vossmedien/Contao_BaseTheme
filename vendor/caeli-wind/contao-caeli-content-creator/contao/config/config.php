<?php

declare(strict_types=1);

/*
 * This file is part of Caeli KI Content-Creator.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/contao-caeli-content-creator
 */

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['caeli_content_creator'] = [
    'tables' => ['tl_caeli_content_creator'],
    'icon'   => 'bundles/caeliwindcontaocaelicontentcreator/icons/content-creator.svg',
];

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_caeli_content_creator'] = 'CaeliWind\ContaoCaeliContentCreator\Model\CaeliContentCreatorModel';
