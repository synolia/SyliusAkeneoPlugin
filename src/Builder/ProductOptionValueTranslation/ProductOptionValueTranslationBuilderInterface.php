<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValueTranslation;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface ProductOptionValueTranslationBuilderInterface
{
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
