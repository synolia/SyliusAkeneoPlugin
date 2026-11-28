<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\Table;

use Sylius\Component\Product\Model\ProductAttributeInterface;

class SelectTableProductAttributeValueProcessor implements TableProductAttributeValueProcessorInterface
{
    private const DEFAULT_PRIORITY = 100;

    private const TYPE = 'select';

    public static function getDefaultPriority(): int
    {
        return self::DEFAULT_PRIORITY;
    }

    #[\Override]
    public function support(
        ProductAttributeInterface $attribute,
        array $tableConfiguration,
        string $locale,
        ?string $scope,
        $value,
        array $context = [],
    ): bool {
        return
            array_key_exists('code', $tableConfiguration) &&
            array_key_exists('labels', $tableConfiguration) &&
            array_key_exists('options', $tableConfiguration) &&
            $tableConfiguration['data_type'] === self::TYPE;
    }

    #[\Override]
    public function getValue(
        ProductAttributeInterface $attribute,
        array $tableConfiguration,
        string $locale,
        ?string $scope,
        $value,
        array $context = [],
    ) {
        foreach ($tableConfiguration['options'] as $option) {
            if ($option['code'] !== $value) {
                continue;
            }

            return $option['labels'][$locale];
        }

        return $value;
    }
}
