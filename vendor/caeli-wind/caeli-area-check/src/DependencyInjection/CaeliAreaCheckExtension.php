<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAreaCheckBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class CaeliAreaCheckExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Container-Parameter für die Bundle-Übersetzungen setzen
        $container->setParameter('caeli_area_check.translations_path', __DIR__ . '/../../translations');
    }
} 