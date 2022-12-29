<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\ReferenceEntity\ReferenceEntityAttributeValueProcessorInterface;

interface ProductRefEntityAttributeValueValueBuilderProviderInterface
{
    /**
     * @param mixed $value
     */
    public function getProcessor(
        string $attributeCode,
        string $referenceEntityCode,
        string $subAttributeCode,
        string $locale,
        string $scope,
        $value,
        array $context = [],
    ): ReferenceEntityAttributeValueProcessorInterface;
}
