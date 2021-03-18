<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilderInterface;
use Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler\AkeneoAttributeTypeMatcherCompilerPass;
use Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler\AkeneoAttributeValueValueBuilderCompilerPass;
use Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler\AkeneoReferenceentityAttributeTypeMatcherCompilerPass;
use Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler\AkeneoTaskCompilerPass;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcherInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcherInterface;

final class SynoliaSyliusAkeneoPlugin extends Bundle
{
    use SyliusPluginTrait;

    public const VERSION = '0.1.0';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container
            ->registerForAutoconfiguration(AkeneoTaskInterface::class)
            ->addTag(AkeneoTaskInterface::TAG_ID);
        $container->addCompilerPass(new AkeneoTaskCompilerPass());
        $container
            ->registerForAutoconfiguration(AttributeTypeMatcherInterface::class)
            ->addTag(AttributeTypeMatcherInterface::TAG_ID);
        $container
            ->registerForAutoconfiguration(ReferenceEntityAttributeTypeMatcherInterface::class)
            ->addTag(ReferenceEntityAttributeTypeMatcherInterface::TAG_ID);
        $container
            ->registerForAutoconfiguration(ProductAttributeValueValueBuilderInterface::class)
            ->addTag(ProductAttributeValueValueBuilderInterface::TAG_ID);

        $container->addCompilerPass(new AkeneoAttributeTypeMatcherCompilerPass());
        $container->addCompilerPass(new AkeneoReferenceentityAttributeTypeMatcherCompilerPass());
        $container->addCompilerPass(new AkeneoAttributeValueValueBuilderCompilerPass());
    }
}
