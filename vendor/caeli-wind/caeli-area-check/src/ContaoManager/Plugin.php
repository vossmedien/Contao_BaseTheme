<?php

namespace CaeliWind\CaeliAreaCheckBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use CaeliWind\CaeliAreaCheckBundle\CaeliAreaCheckBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(CaeliAreaCheckBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
} 