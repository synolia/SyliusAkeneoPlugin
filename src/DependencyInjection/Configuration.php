<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('synolia_sylius_akeneo');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('api_configuration')
                ->children()
                    ->scalarNode('base_url')
//                        ->cannotBeEmpty()
                        ->info('')
                        ->defaultValue('')
                    ->end()
                    ->scalarNode('username')
//                        ->cannotBeEmpty()
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('password')
//                        ->cannotBeEmpty()
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('client_id')
//                        ->cannotBeEmpty()
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('client_secret')
//                        ->cannotBeEmpty()
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('edition')
//                        ->cannotBeEmpty()
                        ->info('')
                        ->example('')
                    ->end()
                    ->integerNode('pagination')
//                        ->cannotBeEmpty()
                        ->info('')
                        ->defaultValue(100)
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
