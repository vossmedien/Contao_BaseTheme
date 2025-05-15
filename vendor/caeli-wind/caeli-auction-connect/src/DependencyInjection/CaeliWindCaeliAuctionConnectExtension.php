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

namespace CaeliWind\CaeliAuctionConnect\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CaeliWindCaeliAuctionConnectExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        // Versuche, den Alias dynamisch zu bekommen, falls Configuration nicht direkt passt.
        if (class_exists(Configuration::class) && defined(Configuration::class.'::ROOT_KEY')) {
            return Configuration::ROOT_KEY;
        }
        // Fallback, falls Configuration nicht existiert oder ROOT_KEY nicht definiert ist.
        $className = get_class($this);
        $bundleNamePart = substr(strrchr($className, '\\'), 1);
        $bundleName = str_replace('Extension', '', $bundleNamePart);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_\$0', $bundleName));
    }

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Die Konfiguration wird hier nicht explizit verarbeitet, da wir keine Bundle-Konfiguration haben,
        // aber der Code ist für den Fall vorbereitet, dass eine Configuration.php existiert.
        // $configuration = new Configuration();
        // $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        // Lade Yaml-Dateien nur, wenn sie existieren.
        if (file_exists(__DIR__.'/../../config/parameters.yaml')) {
            $loader->load('parameters.yaml');
        }
        if (file_exists(__DIR__.'/../../config/services.yaml')) {
            $loader->load('services.yaml');
        }
        if (file_exists(__DIR__.'/../../config/listener.yaml')) {
            $loader->load('listener.yaml');
        }

        // Beispiel für Parameter setzen, falls Konfiguration vorhanden wäre:
        // $rootKey = $this->getAlias();
        // if (isset($config['foo']['bar'])) {
        //    $container->setParameter($rootKey.'.foo.bar', $config['foo']['bar']);
        // }

        // Twig Namespace und Pfad Registrierung wurde in die Haupt-Bundle-Klasse verschoben (build Methode)
    }
}
