<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAreaCheckBundle;

use CaeliWind\CaeliAreaCheckBundle\DependencyInjection\CaeliAreaCheckBundleExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CaeliAreaCheckBundle extends Bundle
{
    public function getContainerExtension(): CaeliAreaCheckBundleExtension
    {
        return new CaeliAreaCheckBundleExtension();
    }
} 