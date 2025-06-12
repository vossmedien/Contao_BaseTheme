<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-ab-test
 */

namespace Vsm\VsmAbTest;

use Vsm\VsmAbTest\DependencyInjection\CaeliWindCaeliAbTestExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CaeliWindCaeliAbTest extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): CaeliWindCaeliAbTestExtension
    {
        return new CaeliWindCaeliAbTestExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }
}
