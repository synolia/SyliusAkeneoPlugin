<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AkeneoTaskCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(TaskProvider::class)) {
            return;
        }

        $definition = $container->getDefinition(TaskProvider::class);

        $taggedServices = $container->findTaggedServiceIds(AkeneoTaskInterface::TAG_ID);
        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addTask', [new Reference($id)]);
        }
    }
}
