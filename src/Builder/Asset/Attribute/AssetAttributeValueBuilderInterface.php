<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute;

interface AssetAttributeValueBuilderInterface
{
    public const TAG_ID = 'sylius.akeneo.asset_value_builder';

    public function support(string $assetFamilyCode, string $attributeCode): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function build(string $assetFamilyCode, string $assetCode, ?string $locale, ?string $scope, $value);
}
