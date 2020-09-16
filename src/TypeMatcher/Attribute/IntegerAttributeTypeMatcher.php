<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\IntegerAttributeType;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\IntegerProductAttributeValueValueBuilder;

final class IntegerAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPES = ['pim_catalog_number', 'integer', 'percent'];

    public function getType(): string
    {
        return IntegerAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return \in_array($akeneoType, self::SUPPORTED_TYPES, true);
    }

    public function getBuilder(): string
    {
        return IntegerProductAttributeValueValueBuilder::class;
    }
}
