<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Hubspot Connect.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-hubspot-connect
 */

namespace CaeliWind\CaeliHubspotConnect\ContaoManager;

use CaeliWind\CaeliHubspotConnect\CaeliWindCaeliHubspotConnect;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{
    /**
     * @return array
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(CaeliWindCaeliHubspotConnect::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
