<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Synolia\SyliusAkeneoPlugin\Builder\Attribute\ReferenceEntityAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntityAttributeType;

final class ReferenceEntityAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'akeneo_reference_entity';

    public function getType(): string
    {
        return ReferenceEntityAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return self::SUPPORTED_TYPE === $akeneoType;
    }

    public function getBuilder(): string
    {
        return ReferenceEntityAttributeValueValueBuilder::class;
    }

    public function getTypeClassName(): string
    {
        return ReferenceEntityAttributeType::class;
    }
}
