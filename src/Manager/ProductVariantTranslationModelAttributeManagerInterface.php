<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Sylius\Component\Core\Model\ProductVariantInterface;

interface ProductVariantTranslationModelAttributeManagerInterface
{
    public function hasRequiredMethodForAttribute(string $attributeCode): bool;

    public function setAkeneoAttributeToProductTranslationModel(
        ProductVariantInterface $productVariant,
        string $attributeCode,
        array $translations,
        string $scope
    ): void;
}
