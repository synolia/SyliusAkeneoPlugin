<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoAxesEnum;

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
                        ->info('')
                        ->defaultValue('')
                    ->end()
                    ->scalarNode('username')
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('password')
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('client_id')
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('client_secret')
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('edition')
                        ->info('')
                        ->example('')
                    ->end()
                    ->scalarNode('axe_as_model')
                        ->info('')
                        ->example('')
                        ->defaultValue(AkeneoAxesEnum::FIRST)
                    ->end()
                    ->integerNode('pagination')
                        ->info('')
                        ->defaultValue(100)
                    ->end()
                ->end()
            ->end()

            ->arrayNode('category_configuration')
                ->children()
                    ->arrayNode('root_category_codes')
                        ->scalarPrototype()->defaultValue([])->end()
                    ->end()
                    ->arrayNode('excluded_category_codes')
                        ->scalarPrototype()->defaultValue([])->end()
                    ->end()
                    ->booleanNode('use_akeneo_positions')->defaultFalse()->end()
                ->end()
            ->end()

            ->arrayNode('locale_mappings')->defaultValue([])
                ->arrayPrototype()->example('fr_FR')
                    ->scalarPrototype()->example(['fr_CH', 'fr_CA', 'fr_FR'])->end()
                ->end()
            ->end()

            ->arrayNode('excluded_attributes')
                ->scalarPrototype()->example(['supplier'])->defaultValue([])->end()
            ->end()

        ->end()
        ;

        return $treeBuilder;
    }
}
