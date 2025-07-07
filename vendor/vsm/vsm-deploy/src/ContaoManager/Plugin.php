<?php

declare(strict_types=1);

/*
 * This file is part of VSM Deploy.
 *
 * (c) VSM 2025
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace VSM\VsmDeploy\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use VSM\VsmDeploy\VsmDeploy;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(VsmDeploy::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['caeli-wind/caeli-deploy']),
        ];
    }
}