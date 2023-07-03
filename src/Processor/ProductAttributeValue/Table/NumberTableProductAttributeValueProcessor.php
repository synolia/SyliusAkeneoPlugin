<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\Table;

use Sylius\Component\Product\Model\ProductAttributeInterface;

class NumberTableProductAttributeValueProcessor implements TableProductAttributeValueProcessorInterface
{
    private const TYPES = [
        'number',
        'text',
        'boolean',
    ];

    public static function getDefaultPriority(): int
    {
        return 200;
    }

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
