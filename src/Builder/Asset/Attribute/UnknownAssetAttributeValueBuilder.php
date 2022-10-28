<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute;

class UnknownAssetAttributeValueBuilder implements AssetAttributeValueBuilderInterface
{
    public function support(string $assetFamilyCode, string $attributeCode): bool
    {
        return false;
    }

    public function build(string $assetFamilyCode, string $assetCode, ?string $locale, ?string $scope, $value)
    {
        // Nothing to see here
    }
}
