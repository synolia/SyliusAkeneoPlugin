<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\CheckboxAttributeType;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\BooleanProductAttributeValueValueBuilder;

final class BooleanAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = ['pim_catalog_boolean', 'checkbox'];

    public function getType(): string
    {
        return CheckboxAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return \in_array($akeneoType, self::SUPPORTED_TYPE, true);
    }

    public function getBuilder(): string
    {
        return BooleanProductAttributeValueValueBuilder::class;
    }

    public function getTypeClassName(): string
    {
        return CheckboxAttributeType::class;
    }
}
