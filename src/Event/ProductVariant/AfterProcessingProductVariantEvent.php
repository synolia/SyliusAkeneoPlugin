<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductVariant;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

final class AfterProcessingProductVariantEvent extends AbstractResourceEvent
{
    public function __construct(array $resource, private ProductVariantInterface $productVariant)
    {
        parent::__construct($resource);
    }

    public function getProductVariant(): ProductVariantInterface
    {
        return $this->productVariant;
    }
}
