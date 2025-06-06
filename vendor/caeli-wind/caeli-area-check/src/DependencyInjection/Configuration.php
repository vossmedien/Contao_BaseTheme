<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAreaCheckBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('caeli_area_check');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('form_ids')
                    ->info('Liste der Formular-IDs die überwacht werden sollen')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        'flaechencheckNotSuccessEN',
                        'flaechencheckNotSuccessDE', 
                        'flaechencheckSuccessEN',
                        'flaechencheckSuccessDE'
                    ])
                ->end()
                ->arrayNode('field_mapping')
                    ->info('Mapping der Formularfeld-Namen')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('lastname_field')
                            ->defaultValue('lastname')
                            ->info('Feldname für Nachname')
                        ->end()
                        ->scalarNode('firstname_field')
                            ->defaultValue('firstname')
                            ->info('Feldname für Vorname')
                        ->end()
                        ->scalarNode('phone_field')
                            ->defaultValue('phone')
                            ->info('Feldname für Telefon')
                        ->end()
                        ->scalarNode('email_field')
                            ->defaultValue('email')
                            ->info('Feldname für E-Mail')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
} 