<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

final class AfterProcessingProductEvent extends AbstractResourceEvent
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
