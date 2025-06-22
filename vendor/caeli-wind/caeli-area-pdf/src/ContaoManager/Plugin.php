<?php

namespace CaeliWind\CaeliAreaPdfBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use CaeliWind\CaeliAreaPdfBundle\CaeliAreaPdfBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(CaeliAreaPdfBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
} 
