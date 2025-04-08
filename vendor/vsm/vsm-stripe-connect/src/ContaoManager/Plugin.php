<?php

declare(strict_types=1);

/*
 * This file is part of vsm-stripe-connect.
 *
 * (c) Christian Voss 2025 <christian@vossmedien.de>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-stripe-connect
 */

namespace Vsm\VsmStripeConnect\ContaoManager;

use Vsm\VsmStripeConnect\VsmStripeConnectBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * @return array
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(VsmStripeConnectBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    /**
     * @return RouteCollection|null
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        $collection = new RouteCollection();

        // Controller-Routes laden
        $loader = $resolver->resolve(__DIR__.'/../../config/routes.yaml');
        if ($loader) {
            $collection->addCollection($loader->load(__DIR__.'/../../config/routes.yaml'));
        }

        // Controller-Attribut-Routen laden
        $resolver->resolve(__DIR__.'/../Controller')
            ->load(__DIR__.'/../Controller');

        return $collection;
    }
}
