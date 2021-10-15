<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

/**
 * Class SettingConfiguration.
 */
final class SettingConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wms_settings');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->useAttributeAsKey('key')
            ->prototype('array')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('type')->defaultValue('text')->end()
                    ->variableNode('options')
                        ->info('The options given to the form builder')
                        ->defaultValue([])
                        ->validate()
                            ->always(static function ($value): array {
                                if (!\is_array($value)) {
                                    throw new InvalidTypeException();
                                }

                                return $value;
                            })
                        ->end()
                    ->end()
                    ->variableNode('constraints')
                        ->info('The constraints on this option. Example, use constraits found in Symfony\Component\Validator\Constraints')
                        ->defaultValue([])
                        ->validate()
                            ->always(static function ($value): array {
                                if (!\is_array($value)) {
                                    throw new InvalidTypeException();
                                }

                                return $value;
                            })
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
