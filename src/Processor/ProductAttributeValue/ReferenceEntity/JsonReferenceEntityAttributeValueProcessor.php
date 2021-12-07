<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\ReferenceEntity;

final class JsonReferenceEntityAttributeValueProcessor implements ReferenceEntityAttributeValueProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return -100;
    }

    /**
     * {@inheritdoc}
     */
    public function support(
        string $attributeCode,
        string $referenceEntityCode,
        string $subAttributeCode,
        string $locale,
        string $scope,
        $value,
        array $context = []
    ): bool {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(
        string $attributeCode,
        string $referenceEntityCode,
        string $subAttributeCode,
        string $locale,
        string $scope,
        $value,
        array $context = []
    ) {
        return $value;
    }
}
