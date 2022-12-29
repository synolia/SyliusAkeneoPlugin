<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\ReferenceEntity;

interface ReferenceEntityAttributeValueProcessorInterface
{
    public const TAG_ID = 'sylius.akeneo.reference_entity_attribute_value_processor';

    public static function getDefaultPriority(): int;

    /**
     * @param mixed $value
     */
    public function support(
        string $attributeCode,
        string $referenceEntityCode,
        string $subAttributeCode,
        string $locale,
        string $scope,
        $value,
        array $context = [],
    ): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function getValue(
        string $attributeCode,
        string $referenceEntityCode,
        string $subAttributeCode,
        string $locale,
        string $scope,
        $value,
        array $context = [],
    );
}
