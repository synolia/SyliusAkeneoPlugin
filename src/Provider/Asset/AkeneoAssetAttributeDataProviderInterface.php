<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

interface AkeneoAssetAttributeDataProviderInterface
{
    /**
     * @param mixed $attributeValues
     *
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException
     *
     * @return mixed|null
     */
    public function getData(string $assetFamilyCode, string $attributeCode, $attributeValues, string $locale, string $scope);
}
