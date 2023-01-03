<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

interface AkeneoReferenceEntityAttributeDataProviderInterface
{
    /**
     * @param mixed $attributeValues
     *
     * @return string|array|null
     */
    public function getData(
        string $referenceEntityCode,
        string $referenceEntityAttributeCode,
        $attributeValues,
        string $locale,
        string $scope,
    );
}
