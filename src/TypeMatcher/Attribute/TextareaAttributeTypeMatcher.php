<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\TextareaAttributeType;

final class TextareaAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'pim_catalog_textarea';

    public function getType(): string
    {
        return TextareaAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return $akeneoType === self::SUPPORTED_TYPE;
    }
}
