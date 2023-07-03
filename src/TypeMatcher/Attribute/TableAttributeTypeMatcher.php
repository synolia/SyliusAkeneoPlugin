<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Synolia\SyliusAkeneoPlugin\Builder\Attribute\TableAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\TableAttributeType;

final class TableAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'pim_catalog_table';

    public function getType(): string
    {
        return TableAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return self::SUPPORTED_TYPE === $akeneoType;
    }

    public function getBuilder(): string
    {
        return TableAttributeValueValueBuilder::class;
    }

    public function getTypeClassName(): string
    {
        return TableAttributeType::class;
    }
}
