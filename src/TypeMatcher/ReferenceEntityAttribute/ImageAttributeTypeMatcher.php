<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute;

use Synolia\SyliusAkeneoPlugin\Builder\TextProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntityImageSubAttributeType;

final class ImageAttributeTypeMatcher implements ReferenceEntityAttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'image';

    public function getType(): string
    {
        return ReferenceEntityImageSubAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return self::SUPPORTED_TYPE === $akeneoType;
    }

    public function getBuilder(): string
    {
        return TextProductAttributeValueValueBuilder::class;
    }
}
