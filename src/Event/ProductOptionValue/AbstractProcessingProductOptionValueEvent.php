<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductOptionValue;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

abstract class AbstractProcessingProductOptionValueEvent extends AbstractResourceEvent
{
    public function __construct(private ProductOptionInterface $productOption, array $resource)
    {
        parent::__construct($resource);
    }

    public function getProductOption(): ProductOptionInterface
    {
        return $this->productOption;
    }
}
