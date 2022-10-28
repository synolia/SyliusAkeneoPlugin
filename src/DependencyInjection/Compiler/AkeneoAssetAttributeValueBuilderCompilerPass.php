<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute\AssetAttributeValueBuilderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AssetValueBuilderProviderInterface;

final class AkeneoAssetAttributeValueBuilderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(AssetValueBuilderProviderInterface::class);

        $taggedServices = $container->findTaggedServiceIds(AssetAttributeValueBuilderInterface::TAG_ID);
        foreach (\array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addBuilder', [new Reference($id)]);
        }
    }
}
