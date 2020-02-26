<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilderInterface;

final class AkeneoAttributeValueValueBuilderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(ProductAttributeValueValueBuilder::class);

        $taggedServices = $container->findTaggedServiceIds(ProductAttributeValueValueBuilderInterface::TAG_ID);
        foreach (\array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addBuilder', [new Reference($id)]);
        }
    }
}
