<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductMediaPayload extends AbstractPayload implements ProductMediaPayloadInterface
{
    private ProductConfiguration $productConfiguration;

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

    public function getProductConfiguration(): ProductConfiguration
    {
        return $this->productConfiguration;
    }

    public function setProductConfiguration(ProductConfiguration $productConfiguration): void
    {
        $this->productConfiguration = $productConfiguration;
    }

    public function createBatchMessage(array $items): BatchMessageInterface
    {
        throw new \InvalidArgumentException();
    }
}
