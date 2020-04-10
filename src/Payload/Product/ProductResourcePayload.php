<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductResourcePayload extends AbstractPayload
{
    /** @var \Sylius\Component\Core\Model\ProductInterface */
    private $product;

    /** @var array */
    private $resource;

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function setProduct(ProductInterface $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getResource(): array
    {
        return $this->resource;
    }

    public function setResource(array $resource): self
    {
        $this->resource = $resource;

        return $this;
    }
}
