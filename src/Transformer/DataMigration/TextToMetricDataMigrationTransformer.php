<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer\DataMigration;

use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\MetricAttributeType;

class TextToMetricDataMigrationTransformer implements DataMigrationTransformerInterface
{
    private const FROM_TYPE = TextAttributeType::TYPE;

    private const TO_TYPE = MetricAttributeType::TYPE;

    public function transform(string $value): array
    {
        $data = explode(' ', $value);

        return [
            'unit' => $data[0],
            'amount' => str_replace(sprintf('%s ', $data[0]), '', $value),
        ];
    }

    public function reverseTransform(array $value): string
    {
        return implode(' ', $value);
    }

    public function support(string $fromType, string $toType): bool
    {
        return $fromType === self::FROM_TYPE && $toType === self::TO_TYPE;
    }
}
