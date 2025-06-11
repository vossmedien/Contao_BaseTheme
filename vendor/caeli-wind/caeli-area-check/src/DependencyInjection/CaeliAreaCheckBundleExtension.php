<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAreaCheckBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

class CaeliAreaCheckBundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Default-Konfiguration aus config.yaml laden
        $defaultConfigPath = __DIR__.'/../../config/config.yaml';
        $defaultConfig = [];
        
        if (file_exists($defaultConfigPath)) {
            $defaultConfig = Yaml::parseFile($defaultConfigPath);
        }
        
        // Default-Konfiguration zu den Configs hinzufügen
        array_unshift($configs, $defaultConfig);
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Konfiguration als Parameter setzen
        $container->setParameter('caeli_area_check.form_ids', $config['form_ids']);
        $container->setParameter('caeli_area_check.field_mapping', $config['field_mapping']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('services.yaml');
    }
} 