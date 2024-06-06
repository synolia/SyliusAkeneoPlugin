<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductVariantMediaPayload extends AbstractPayload implements ProductMediaPayloadInterface
{
    private ProductVariantInterface $productVariant;

    private array $attributes;

    public function getProductVariant(): ProductVariantInterface
    {
        return $this->productVariant;
    }

    public function setProductVariant(ProductVariantInterface $productVariant): self
    {
        $this->productVariant = $productVariant;

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

    public function createBatchMessage(array $items): BatchMessageInterface
    {
        throw new \InvalidArgumentException();
    }
}
