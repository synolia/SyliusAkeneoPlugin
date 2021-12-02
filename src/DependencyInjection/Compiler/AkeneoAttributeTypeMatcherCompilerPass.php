<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcherInterface;

final class AkeneoAttributeTypeMatcherCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(AttributeTypeMatcher::class);

        $taggedServices = $container->findTaggedServiceIds(AttributeTypeMatcherInterface::TAG_ID);
        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addTypeMatcher', [new Reference($id)]);
        }
    }
}
