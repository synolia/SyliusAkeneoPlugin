<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue\DynamicOptionValueBuilderInterface;
use Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValueTranslation\ProductOptionValueTranslationBuilderInterface;
use Synolia\SyliusAkeneoPlugin\DependencyInjection\Compiler\AkeneoTaskCompilerPass;
use Synolia\SyliusAkeneoPlugin\Processor\Category\CategoryProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Product\ProductProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\AkeneoAttributeProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductOptionValue\OptionValuesProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductVariant\ProductVariantProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class SynoliaSyliusAkeneoPlugin extends Bundle implements \Stringable
{
    use SyliusPluginTrait;

    public const VERSION = '3.3.0';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container
            ->registerForAutoconfiguration(AkeneoTaskInterface::class)
            ->addTag(AkeneoTaskInterface::TAG_ID)
        ;
        $container->addCompilerPass(new AkeneoTaskCompilerPass());
        $container
            ->registerForAutoconfiguration(ProductOptionValueTranslationBuilderInterface::class)
            ->addTag(ProductOptionValueTranslationBuilderInterface::TAG_ID)
        ;
        $container
            ->registerForAutoconfiguration(DynamicOptionValueBuilderInterface::class)
            ->addTag(DynamicOptionValueBuilderInterface::TAG_ID)
        ;
        $container
            ->registerForAutoconfiguration(AkeneoAttributeProcessorInterface::class)
            ->addTag(AkeneoAttributeProcessorInterface::TAG_ID)
        ;
    }

    public function __toString(): string
    {
        return 'SynoliaSyliusAkeneoPlugin';
    }
}
