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

namespace CaeliWind\CaeliAuctionConnect\ContaoManager;

use CaeliWind\CaeliAuctionConnect\CaeliWindCaeliAuctionConnect;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class Plugin implements BundlePluginInterface, RoutingPluginInterface, ConfigPluginInterface
{
    /**
     * @return array
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(CaeliWindCaeliAuctionConnect::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    /**
     * @return RouteCollection|null
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        $collection = new RouteCollection();

        // Lade die manuell definierten Routen aus routes.yaml
        $loader = $resolver->resolve(__DIR__.'/../../config/routes.yaml');
        if ($loader) {
            $collection->addCollection($loader->load(__DIR__.'/../../config/routes.yaml'));
        }

        // Lade die Controller-basierenden Routen (wenn vorhanden)
        $resolver = $resolver->resolve(__DIR__.'/../Controller');
        if ($resolver) {
            $collection->addCollection($resolver->load(__DIR__.'/../Controller'));
        }

        return $collection;
    }

    /**
     * Erweitert die Contao-Konfiguration
     */
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        // In Contao 5.x verwenden wir das localconfig-System
        $loader->load(function ($container) {
            $container->prependExtensionConfig('contao', [
                'localconfig' => [
                    'urlSuffix' => '',
                    'folderUrl' => true,
                    'useAutoItem' => true,
                ],
                'search' => [
                    'default_indexer' => [
                        'enable' => false,
                    ],
                ],
            ]);
        });
    }
}
