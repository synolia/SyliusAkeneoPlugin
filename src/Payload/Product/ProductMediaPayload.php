<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductMediaPayload extends AbstractPayload implements ProductMediaPayloadInterface
{
    private \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration $productConfiguration;

    private ProductInterface $product;

    private array $attributes;

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function setProduct(ProductInterface $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getProductConfiguration(): \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration
    {
        return $this->productConfiguration;
    }

    public function setProductConfiguration(\Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration $productConfiguration): void
    {
        $this->productConfiguration = $productConfiguration;
    }
}
