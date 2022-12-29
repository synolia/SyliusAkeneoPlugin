<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductVariant;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

final class BeforeProcessingProductVariantEvent extends AbstractResourceEvent
{
    public function __construct(array $resource, private ProductInterface $product)
    {
        parent::__construct($resource);
    }

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }
}
