<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute;

use Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute\TextProductReferenceEntityAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntityTextSubAttributeType;

final class TextAttributeTypeMatcher implements ReferenceEntityAttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'text';

    public function getType(): string
    {
        return ReferenceEntityTextSubAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return self::SUPPORTED_TYPE === $akeneoType;
    }

    public function getBuilder(): string
    {
        return TextProductReferenceEntityAttributeValueValueBuilder::class;
    }
}
