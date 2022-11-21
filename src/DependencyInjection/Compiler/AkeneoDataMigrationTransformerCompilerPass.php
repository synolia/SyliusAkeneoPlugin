<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Synolia\SyliusAkeneoPlugin\Transformer\DataMigration\DataMigrationTransformer;
use Synolia\SyliusAkeneoPlugin\Transformer\DataMigration\DataMigrationTransformerInterface;

final class AkeneoDataMigrationTransformerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(DataMigrationTransformer::class);

        $taggedServices = $container->findTaggedServiceIds(DataMigrationTransformerInterface::TAG_ID);
        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addDataMigrationTransformer', [new Reference($id)]);
        }
    }
}
