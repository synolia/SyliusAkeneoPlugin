<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\MetricProductAttributeValueValueBuilder;

final class MetricAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = 'pim_catalog_metric';

    public function getType(): string
    {
        return TextAttributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return self::SUPPORTED_TYPE === $akeneoType;
    }

    public function getBuilder(): string
    {
        return MetricProductAttributeValueValueBuilder::class;
    }
}
