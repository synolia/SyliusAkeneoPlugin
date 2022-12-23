<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductOptionValueTranslation;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;

final class AfterProcessingProductOptionValueTranslationEvent extends AbstractProcessingProductOptionValueTranslationEvent
{
    private ProductOptionValueTranslationInterface $productOptionValueTranslation;

    public function __construct(
        ProductOptionInterface $productOption,
        ProductOptionValueInterface $productOptionValue,
        ProductOptionValueTranslationInterface $productOptionValueTranslation,
        string $locale,
        array $resource
    ) {
        parent::__construct($productOption, $productOptionValue, $locale, $resource);

        $this->productOptionValueTranslation = $productOptionValueTranslation;
    }

    public function getProductOptionValueTranslation(): ProductOptionValueTranslationInterface
    {
        return $this->productOptionValueTranslation;
    }
}
