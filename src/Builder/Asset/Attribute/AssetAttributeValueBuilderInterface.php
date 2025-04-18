<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: self::TAG_ID)]
interface AssetAttributeValueBuilderInterface
{
    public const TAG_ID = 'sylius.akeneo.asset_value_builder';

    public function support(string $assetFamilyCode, string $attributeCode): bool;

    /**
     * @return mixed
     */
    public function build(string $assetFamilyCode, string $assetCode, ?string $locale, ?string $scope, mixed $value);
}
