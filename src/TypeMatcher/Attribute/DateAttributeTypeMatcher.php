<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\DateAttributeType;

final class DateAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'pim_catalog_date';

    public function getType(): string
    {
        return DateAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return $akeneoType === self::SUPPORTED_TYPE;
    }
}
