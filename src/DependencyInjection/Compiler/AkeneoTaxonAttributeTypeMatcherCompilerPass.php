<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute\TaxonAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute\TaxonAttributeTypeMatcherInterface;

final class AkeneoTaxonAttributeTypeMatcherCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(TaxonAttributeTypeMatcher::class);

        $taggedServices = $container->findTaggedServiceIds(TaxonAttributeTypeMatcherInterface::TAG_ID);
        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addTypeMatcher', [new Reference($id)]);
        }
    }
}
