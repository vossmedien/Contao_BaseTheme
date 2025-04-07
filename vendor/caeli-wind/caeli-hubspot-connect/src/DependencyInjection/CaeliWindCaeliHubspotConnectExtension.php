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

namespace CaeliWind\CaeliHubspotConnect\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CaeliWindCaeliHubspotConnectExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        // Wird nicht mehr benötigt, da keine Konfiguration über Configuration.php verarbeitet wird
        // return Configuration::ROOT_KEY;
        // Standard Symfony Konvention verwenden
        return 'caeli_wind_caeli_hubspot_connect';
    }

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Entferne die Verarbeitung der Konfiguration über Configuration.php
        // $configuration = new Configuration();
        // $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        // Entferne das Laden der leeren/unnötigen Dateien
        // $loader->load('parameters.yaml');
        $loader->load('services.yaml');
        // $loader->load('listener.yaml');

        // Entferne das Setzen des nicht mehr existierenden Parameters
        // $rootKey = $this->getAlias();
        // $container->setParameter($rootKey.'.foo.bar', $config['foo']['bar']);
    }
}
