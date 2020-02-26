<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\CheckboxAttributeType;

final class BooleanAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'pim_catalog_boolean';

    public function getType(): string
    {
        return CheckboxAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return $akeneoType === self::SUPPORTED_TYPE;
    }
}
