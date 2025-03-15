<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */

namespace Vsm\VsmHelperTools;

use Vsm\VsmHelperTools\DependencyInjection\VsmHelperToolsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class VsmHelperTools extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): VsmHelperToolsExtension
    {
        return new VsmHelperToolsExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtensionName(): string
    {
        return 'vsm_helper_tools';
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
    public function boot(): void
    {
        parent::boot();
    }
}
