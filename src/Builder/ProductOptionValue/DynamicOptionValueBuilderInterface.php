<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: self::TAG_ID)]
interface DynamicOptionValueBuilderInterface
{
    public const TAG_ID = 'sylius.akeneo.dynamic_option_value_builder';

    public static function getDefaultPriority(): int;

    public function support(ProductOptionInterface $productOption, mixed $values, array $context = []): bool;

    public function build(
        ProductOptionInterface $productOption,
        mixed $values,
        array $context = [],
    ): ProductOptionValueInterface;
}
