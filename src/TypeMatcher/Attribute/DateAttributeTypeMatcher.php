<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\DateAttributeType;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\DateProductAttributeValueValueBuilder;

final class DateAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = ['pim_catalog_date', 'date', 'datetime'];

    public function getType(): string
    {
        return DateAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return \in_array($akeneoType, self::SUPPORTED_TYPE, true);
    }

    public function getBuilder(): string
    {
        return DateProductAttributeValueValueBuilder::class;
    }
}
