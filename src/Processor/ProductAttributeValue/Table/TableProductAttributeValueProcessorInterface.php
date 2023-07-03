<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\Table;

use Sylius\Component\Product\Model\ProductAttributeInterface;

interface TableProductAttributeValueProcessorInterface
{
    public const TAG_ID = 'sylius.akeneo.table_product_attribute_value_processor';

    public static function getDefaultPriority(): int;

    /**
     * @param mixed $value
     */
    public function support(
        ProductAttributeInterface $attribute,
        array $tableConfiguration,
        string $locale,
        ?string $scope,
        $value,
        array $context = [],
    ): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function getValue(
        ProductAttributeInterface $attribute,
        array $tableConfiguration,
        string $locale,
        ?string $scope,
        $value,
        array $context = [],
    );
}
