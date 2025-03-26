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

namespace CaeliWind\CaeliGoogleNewsFetch\ContaoManager;

use CaeliWind\CaeliGoogleNewsFetch\CaeliWindCaeliGoogleNewsFetch;
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
            BundleConfig::create(CaeliWindCaeliGoogleNewsFetch::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        return $resolver
            ->resolve(__DIR__ . '/../../config/routes.yaml')
            ->load(__DIR__ . '/../../config/routes.yaml');
    }
}
