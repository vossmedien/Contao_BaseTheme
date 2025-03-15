<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */

namespace Vsm\VsmHelperTools\ContaoManager;

use Vsm\VsmHelperTools\VsmHelperTools;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * @return array
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(VsmHelperTools::class)
                ->setLoadAfter([ContaoCoreBundle::class, ContaoCalendarBundle::class]),
        ];
    }
    
    /**
     * Registriert die benutzerdefinierten Routing-Dateien
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        $files = [
            __DIR__.'/../../config/routes.yaml',
            __DIR__.'/../../config/routing.yml'
        ];
        
        $collection = null;
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            
            $loader = $resolver->resolve($file);
            
            if (!$loader) {
                continue;
            }
            
            $routeCollection = $loader->load($file);
            
            if (null === $collection) {
                $collection = $routeCollection;
            } else {
                $collection->addCollection($routeCollection);
            }
        }
        
        return $collection;
    }
}
