<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

final class AfterProcessingProductEvent extends AbstractResourceEvent
{
    public function __construct(
        array $resource,
        private ProductInterface $originalProduct,
        private ProductInterface $finalProduct,
    ) {
        parent::__construct($resource);
    }

    /** @deprecated will be removed in next major release. Use getFinalProduct() instead. */
    public function getProduct(): ProductInterface
    {
        return $this->finalProduct;
    }

    public function getOriginalProduct(): ProductInterface
    {
        return $this->originalProduct;
    }

    public function getFinalProduct(): ProductInterface
    {
        return $this->finalProduct;
    }
}
