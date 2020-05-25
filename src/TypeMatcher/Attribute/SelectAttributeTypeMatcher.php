<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Synolia\SyliusAkeneoPlugin\Builder\SelectProductAttributeValueValueBuilder;

final class SelectAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPES = ['pim_catalog_simpleselect', 'pim_catalog_multiselect', 'select'];

    public function getType(): string
    {
        return SelectAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return \in_array($akeneoType, self::SUPPORTED_TYPES, true);
    }

    public function isMultiple(string $akeneoType): bool
    {
        return $akeneoType === 'pim_catalog_multiselect';
    }

    public function getBuilder(): string
    {
        return SelectProductAttributeValueValueBuilder::class;
    }
}
