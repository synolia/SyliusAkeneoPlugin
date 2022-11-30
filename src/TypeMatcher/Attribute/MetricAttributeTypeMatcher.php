<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Synolia\SyliusAkeneoPlugin\Builder\Attribute\MetricProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\MetricAttributeType;

final class MetricAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'pim_catalog_metric';

    public function getType(): string
    {
        return MetricAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return self::SUPPORTED_TYPE === $akeneoType;
    }

    public function getBuilder(): string
    {
        return MetricProductAttributeValueValueBuilder::class;
    }

    public function getTypeClassName(): string
    {
        return MetricAttributeType::class;
    }
}
