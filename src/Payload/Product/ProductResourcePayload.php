<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductResourcePayload extends AbstractPayload
{
    private ProductInterface $product;

    private array $resource;

    private array $family;

    private string $scope;

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

    public function getFamily(): array
    {
        return $this->family;
    }

    public function setFamily(array $family): self
    {
        $this->family = $family;

        return $this;
    }

    public function getProductNameAttribute(): string
    {
        return $this->family['attribute_as_label'];
    }

    public function getProductMainImageAttribute(): string
    {
        return $this->family['attribute_as_image'];
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }
}
