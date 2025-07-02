<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Deploy.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-deploy
 */

namespace CaeliWind\CaeliDeploy;

use CaeliWind\CaeliDeploy\DependencyInjection\CaeliWindCaeliDeployExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class CaeliWindCaeliDeploy extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): CaeliWindCaeliDeployExtension
    {
        return new CaeliWindCaeliDeployExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function loadContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/../config/services.yaml');
    }
}
