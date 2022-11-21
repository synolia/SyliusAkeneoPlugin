<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\TextareaAttributeType;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\TextProductAttributeValueValueBuilder;

final class TextareaAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = ['pim_catalog_textarea', 'textarea'];

    public function getType(): string
    {
        return TextareaAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return \in_array($akeneoType, self::SUPPORTED_TYPE, true);
    }

    public function getBuilder(): string
    {
        return TextProductAttributeValueValueBuilder::class;
    }

    public function getTypeClassName(): string
    {
        return TextareaAttributeType::class;
    }
}
