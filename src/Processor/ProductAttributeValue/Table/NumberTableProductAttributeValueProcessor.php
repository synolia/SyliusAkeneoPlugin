<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\Table;

use Sylius\Component\Product\Model\ProductAttributeInterface;

class NumberTableProductAttributeValueProcessor implements TableProductAttributeValueProcessorInterface
{
    private const DEFAULT_PRIORITY = 200;

    private const TYPES = [
        'number',
        'text',
        'boolean',
    ];

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
            in_array($tableConfiguration['data_type'], self::TYPES, true);
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
        return $value;
    }
}
