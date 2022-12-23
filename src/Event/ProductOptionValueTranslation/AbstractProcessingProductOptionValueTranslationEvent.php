<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductOptionValueTranslation;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

abstract class AbstractProcessingProductOptionValueTranslationEvent extends AbstractResourceEvent
{
    private ProductOptionInterface $productOption;

    private ProductOptionValueInterface $productOptionValue;

    private string $locale;

    public function __construct(
        ProductOptionInterface $productOption,
        ProductOptionValueInterface $productOptionValue,
        string $locale,
        array $resource
    ) {
        parent::__construct($resource);

        $this->productOption = $productOption;
        $this->productOptionValue = $productOptionValue;
        $this->locale = $locale;
    }

    public function getProductOption(): ProductOptionInterface
    {
        return $this->productOption;
    }

    public function getProductOptionValue(): ProductOptionValueInterface
    {
        return $this->productOptionValue;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
