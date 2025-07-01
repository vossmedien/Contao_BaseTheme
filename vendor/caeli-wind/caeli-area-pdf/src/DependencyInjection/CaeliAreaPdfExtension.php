<?php

namespace CaeliWind\CaeliAreaPdfBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CaeliAreaPdfExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        
        $loader->load('services.yaml');
        
        // Default-Parameter setzen
        $container->setParameter('caeli_area_pdf.api_url', '');
        $container->setParameter('caeli_area_pdf.timeout', 30);
        $container->setParameter('caeli_area_pdf.connect_timeout', 10);
    }
} 