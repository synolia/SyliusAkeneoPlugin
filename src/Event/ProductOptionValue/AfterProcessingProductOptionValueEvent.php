<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductOptionValue;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;

final class AfterProcessingProductOptionValueEvent extends AbstractProcessingProductOptionValueEvent
{
    public function __construct(ProductOptionInterface $productOption, private ProductOptionValueInterface $productOptionValue, array $resource)
    {
        parent::__construct($productOption, $resource);
    }

    public function getProductOptionValue(): ProductOptionValueInterface
    {
        return $this->productOptionValue;
    }
}
