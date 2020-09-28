<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute;

use Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute\TextProductReferenceEntityAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntityNumberSubAttributeType;

final class NumberAttributeTypeMatcher implements ReferenceEntityAttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'number';

    public function getType(): string
    {
        return ReferenceEntityNumberSubAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return self::SUPPORTED_TYPE === $akeneoType;
    }

    public function getBuilder(): string
    {
        return TextProductReferenceEntityAttributeValueValueBuilder::class;
    }

    public function getStorageType(): string
    {
        return 'integer';
    }
}
