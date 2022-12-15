<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface ProductGroupInterface extends ResourceInterface
{
    public function setModel(string $model): self;

    public function getModel(): string;

    public function getParent(): ?self;

    public function setParent(?self $parent): self;

    /**
     * @return array|string[]
     */
    public function getVariationAxes(): array;

    public function setVariationAxes(array $variationAxes): self;

    public function addVariationAxe(string $variationAxe): self;

    public function removeVariationAxe(string $variationAxe): self;

    public function getFamily(): string;

    public function setFamily(string $family): self;

    public function getFamilyVariant(): string;

    public function setFamilyVariant(string $familyVariant): self;

    /**
     * @return Collection|ProductInterface[]
     */
    public function getProducts(): Collection;

    public function addProduct(ProductInterface $product): self;

    public function removeProduct(ProductInterface $product): self;

    /**
     * @return array|string[]
     */
    public function getAssociations(): array;

    public function setAssociations(array $associations): self;

    public function addAssociation(string $association): self;

    public function removeAssociation(string $association): self;
}
