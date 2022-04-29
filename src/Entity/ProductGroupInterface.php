<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface ProductGroupInterface extends ResourceInterface
{
    public function setProductParent(string $productParent): self;

    public function getProductParent(): string;

    /**
     * @return array|string[]
     */
    public function getVariationAxes(): array;

    public function setVariationAxes(array $variationAxes): self;

    public function addVariationAxe(string $variationAxe): self;

    public function removeVariationAxe(string $variationAxe): self;

    public function getFamily(): string;

    public function setFamily(string $family): self;

    /**
     * @return Collection|ProductInterface[]
     */
    public function getProducts(): Collection;

    public function addProduct(ProductInterface $product): self;

    public function removeProduct(ProductInterface $product): self;
}
