<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductVariant;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

final class AfterProcessingProductVariantEvent extends AbstractResourceEvent
{
    public function __construct(
        array $resource,
        private ProductVariantInterface $originalProductVariant,
        private ProductVariantInterface $finalProductVariant,
    ) {
        parent::__construct($resource);
    }

    /** @deprecated will be removed in next major release. Use getFinalProduct() instead. */
    public function getProductVariant(): ProductVariantInterface
    {
        return $this->finalProductVariant;
    }

    public function getOriginalProduct(): ProductInterface
    {
        return $this->originalProductVariant;
    }

    public function getFinalProduct(): ProductInterface
    {
        return $this->finalProductVariant;
    }
}
