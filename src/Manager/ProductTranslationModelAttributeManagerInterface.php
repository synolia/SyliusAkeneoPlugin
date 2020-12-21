<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Sylius\Component\Core\Model\ProductInterface;

interface ProductTranslationModelAttributeManagerInterface
{
    public function hasRequiredMethodForAttribute(string $attributeCode): bool;

    public function setAkeneoAttributeToProductTranslationModel(
        ProductInterface $productTranslation,
        string $attributeCode,
        array $translations,
        string $scope
    ): void;
}
