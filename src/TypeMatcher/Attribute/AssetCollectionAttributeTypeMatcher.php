<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Synolia\SyliusAkeneoPlugin\Builder\Attribute\AssetAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\AssetAttributeType;

final class AssetCollectionAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'pim_catalog_asset_collection';

    public function getType(): string
    {
        return AssetAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return self::SUPPORTED_TYPE === $akeneoType;
    }

    public function getBuilder(): string
    {
        return AssetAttributeValueValueBuilder::class;
    }

    public function getTypeClassName(): string
    {
        return AssetAttributeType::class;
    }
}
