<?php

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use iq2\CEAutoWrapperCloseBundle\Iq2CEAutoWrapperCloseBundle;
use MadeYourDay\RockSolidCustomElements\RockSolidCustomElementsBundle;



class ContaoManagerPlugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(Iq2CEAutoWrapperCloseBundle::class)->setLoadAfter([ContaoCoreBundle::class, RockSolidCustomElementsBundle::class])
        ];
    }

}