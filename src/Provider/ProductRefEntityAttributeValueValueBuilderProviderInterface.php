<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\ReferenceEntity\ReferenceEntityAttributeValueProcessorInterface;

interface ProductRefEntityAttributeValueValueBuilderProviderInterface
{
    /**
     * @param string $attributeCode
     * @param string $referenceEntityCode
     * @param string $subAttributeCode
     * @param string $locale
     * @param string $scope
     * @param mixed $value
     * @param array $context
     *
     * @return \Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\ReferenceEntity\ReferenceEntityAttributeValueProcessorInterface
     */
    public function getProcessor(
        string $attributeCode,
        string $referenceEntityCode,
        string $subAttributeCode,
        string $locale,
        string $scope,
        $value,
        array $context = []
    ): ReferenceEntityAttributeValueProcessorInterface;
}
