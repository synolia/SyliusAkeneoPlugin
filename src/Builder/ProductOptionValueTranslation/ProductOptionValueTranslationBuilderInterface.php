<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValueTranslation;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;

interface ProductOptionValueTranslationBuilderInterface
{
    public const TAG_ID = 'sylius.akeneo.dynamic_option_value_translation_builder';

    public static function getDefaultPriority(): int;

    public function support(
        ProductOptionInterface $productOption,
        ProductOptionValueInterface $productOptionValue,
        string $locale,
        array $attributeValues,
    ): bool;

    public function build(
        ProductOptionInterface $productOption,
        ProductOptionValueInterface $productOptionValue,
        string $locale,
        array $attributeValues,
    ): ProductOptionValueTranslationInterface;
}
