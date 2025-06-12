<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-ab-test
 */

namespace Vsm\VsmAbTest\ContaoManager;

use Vsm\VsmAbTest\CaeliWindCaeliAbTest;
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
            BundleConfig::create(CaeliWindCaeliAbTest::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
