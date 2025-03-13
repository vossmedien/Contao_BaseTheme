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

use Vsm\VsmHelperTools\DependencyInjection\VsmVsmHelperToolsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class VsmVsmHelperTools extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): VsmVsmHelperToolsExtension
    {
        return new VsmVsmHelperToolsExtension();
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
        
        // Unterstützung für alte Namespace
        $this->registerOldNamespaceAliases();
    }
    
    /**
     * Registriert Aliases für alte Namespaces.
     */
    private function registerOldNamespaceAliases(): void
    {
        // Helper-Klassen
        $helpers = [
            'HeadlineHelper',
            'ImageHelper',
            'VideoHelper',
            'ButtonHelper',
            'BasicHelper',
            'EnvHelper',
            'PaymentFormHelper',
            'GlobalElementConfig'
        ];
        
        foreach ($helpers as $helper) {
            $oldClass = 'VSM_HelperFunctions\\' . $helper;
            $newClass = 'Vsm\\VsmHelperTools\\Helper\\' . $helper;
            
            if (!class_exists($oldClass, false) && class_exists($newClass, false)) {
                class_alias($newClass, $oldClass);
            }
        }
    }
}
