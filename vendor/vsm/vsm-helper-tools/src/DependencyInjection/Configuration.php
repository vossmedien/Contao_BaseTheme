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

namespace Vsm\VsmHelperTools\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_KEY = 'vsm_helper_tools';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_KEY);

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('stripe')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('public_key')
                            ->info('Stripe Public API Key')
                            ->defaultValue('%env(STRIPE_PUBLIC_KEY)%')
                        ->end()
                        ->scalarNode('secret_key')
                            ->info('Stripe Secret API Key')
                            ->defaultValue('%env(STRIPE_SECRET_KEY)%')
                        ->end()
                        ->scalarNode('webhook_secret')
                            ->info('Stripe Webhook Secret')
                            ->defaultValue('%env(STRIPE_WEBHOOK_SECRET)%')
                        ->end()
                    ->end()
                ->end() // end stripe
            ->end()
        ;

        return $treeBuilder;
    }
}
