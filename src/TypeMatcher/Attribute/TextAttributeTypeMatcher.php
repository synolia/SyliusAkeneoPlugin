<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\TextProductAttributeValueValueBuilder;

final class TextAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPES = [
        'pim_catalog_identifier',
        'pim_catalog_text',
        'text',
    ];

    public function getType(): string
    {
        return TextAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return \in_array($akeneoType, self::SUPPORTED_TYPES, true);
    }

    public function getBuilder(): string
    {
        return TextProductAttributeValueValueBuilder::class;
    }

    public function getTypeClassName(): string
    {
        return TextAttributeType::class;
    }
}
