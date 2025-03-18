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

namespace Vsm\VsmHelperTools\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

class VsmHelperToolsExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Lade die Services-Konfiguration aus dem Resources-Verzeichnis
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');
        
        // Lade die Hauptkonfiguration aus dem config-Verzeichnis
        $mainLoader = new YamlFileLoader(
            $container, 
            new FileLocator(__DIR__.'/../../config')
        );
        $mainLoader->load('services.yaml');

        // Konfiguration verarbeiten
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        // Hinweis: Stripe-Konfiguration wurde zu vsm-stripe-connect migriert
    }

    /**
     * Lädt die Routing-Konfiguration
     */
    public function prepend(ContainerBuilder $container): void
    {
        // Framework-Konfiguration für Routing
        $frameworkConfig = [];
        $frameworkConfig['router']['resource'] = __DIR__ . '/../../config/routing.yml';
        $frameworkConfig['router']['type'] = 'yaml';
        $frameworkConfig['router']['utf8'] = true;

        $container->prependExtensionConfig('framework', $frameworkConfig);
    }

    public function getAlias(): string
    {
        return 'vsm_helper_tools';
    }
} 