<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductOptionValueTranslation;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

abstract class AbstractProcessingProductOptionValueTranslationEvent extends AbstractResourceEvent
{
    public function __construct(
        private ProductOptionInterface $productOption,
        private ProductOptionValueInterface $productOptionValue,
        private string $locale,
        array $resource,
    ) {
        parent::__construct($resource);
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
