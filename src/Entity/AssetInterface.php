<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface AssetInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getType(): ?string;

    public function setType(string $type): self;

    /**
     * @return Collection|ProductInterface[]
     */
    public function getOwner(): Collection;

    public function addOwner(ProductInterface $owner): self;

    public function removeOwner(ProductInterface $owner): self;

    /**
     * @return Collection|ProductVariantInterface[]
     */
    public function getProductVariants(): Collection;

    public function addProductVariant(ProductVariantInterface $productVariant): self;

    public function removeProductVariant(ProductVariantInterface $productVariant): self;

    public function getContent(): array;

    public function setContent(array $content): self;

    public function getFamilyCode(): string;

    public function setFamilyCode(string $familyCode): self;

    public function getAssetCode(): string;

    public function setAssetCode(string $assetCode): self;

    public function getAttributeCode(): string;

    public function setAttributeCode(string $attributeCode): self;

    public function getLocale(): string;

    public function setLocale(string $locale): self;

    public function getScope(): string;

    public function setScope(string $scope): self;
}
