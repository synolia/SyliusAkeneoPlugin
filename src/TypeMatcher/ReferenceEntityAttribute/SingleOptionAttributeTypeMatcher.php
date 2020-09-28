<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute;

use Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute\SelectProductReferenceEntityAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntitySelectSubAttributeType;

final class SingleOptionAttributeTypeMatcher implements ReferenceEntityAttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'single_option';

    public function getType(): string
    {
        return ReferenceEntitySelectSubAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return self::SUPPORTED_TYPE === $akeneoType;
    }

    public function getBuilder(): string
    {
        return SelectProductReferenceEntityAttributeValueValueBuilder::class;
    }

    public function getStorageType(): string
    {
        return 'json';
    }
}
