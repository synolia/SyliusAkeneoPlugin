<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValueTranslation;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Builder\ProductOptionValueTranslation\ProductOptionValueTranslationBuilderNotFoundException;

interface ProductOptionValueTranslationBuilderProcessorInterface
{
    /**
     * @throws ProductOptionValueTranslationBuilderNotFoundException
     */
    public function build(
        ProductOptionInterface $productOption,
        ProductOptionValueInterface $productOptionValue,
        string $locale,
        array $attributeValues
    ): ProductOptionValueTranslationInterface;
}
