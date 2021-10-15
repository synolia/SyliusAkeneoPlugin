<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductVariant;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

class AfterProcessingProductVariantEvent extends AbstractResourceEvent
{
    private ProductVariantInterface $productVariant;

    public function __construct(array $resource, ProductVariantInterface $taxon)
    {
        parent::__construct($resource);

        $this->productVariant = $taxon;
    }

    public function getProductVariant(): ProductVariantInterface
    {
        return $this->productVariant;
    }
}
