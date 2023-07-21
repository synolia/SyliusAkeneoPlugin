<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute\TaxonAttributeValueBuilder;
use Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute\TaxonAttributeValueBuilderInterface;

final class AkeneoTaxonAttributeValueBuilderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(TaxonAttributeValueBuilder::class);

        $taggedServices = $container->findTaggedServiceIds(TaxonAttributeValueBuilderInterface::TAG_ID);
        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addBuilder', [new Reference($id)]);
        }
    }
}
