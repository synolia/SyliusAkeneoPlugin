<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

final class AkeneoAssetAttributeDataProvider implements AkeneoAssetAttributeDataProviderInterface
{
    public function __construct(
        private AkeneoAssetAttributePropertiesProvider $akeneoAssetAttributePropertiesProvider,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private AssetValueBuilderProviderInterface $assetAttributeValueBuilderProvider,
    ) {
    }

    /**
     * @throws MissingLocaleTranslationOrScopeException
     * @throws MissingLocaleTranslationException
     * @throws MissingScopeException
     * @throws TranslationNotFoundException
     */
    public function getData(
        string $assetFamilyCode,
        string $attributeCode,
        $attributeValues,
        string $locale,
        string $scope,
    ) {
        $akeneoLocale = $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($locale);

        if (!$this->akeneoAssetAttributePropertiesProvider->isScopable($assetFamilyCode, $attributeCode) &&
            !$this->akeneoAssetAttributePropertiesProvider->isLocalizable($assetFamilyCode, $attributeCode)) {
            return $attributeValues[0]['data'];
        }

        if ($this->akeneoAssetAttributePropertiesProvider->isScopable($assetFamilyCode, $attributeCode) &&
            !$this->akeneoAssetAttributePropertiesProvider->isLocalizable($assetFamilyCode, $attributeCode)) {
            return $this->getByScope($assetFamilyCode, $attributeCode, $attributeValues, $scope);
        }

        if ($this->akeneoAssetAttributePropertiesProvider->isScopable($assetFamilyCode, $attributeCode) &&
            $this->akeneoAssetAttributePropertiesProvider->isLocalizable($assetFamilyCode, $attributeCode)) {
            return $this->getByLocaleAndScope($assetFamilyCode, $attributeCode, $attributeValues, $akeneoLocale, $scope);
        }

        if (!$this->akeneoAssetAttributePropertiesProvider->isScopable($assetFamilyCode, $attributeCode) &&
            $this->akeneoAssetAttributePropertiesProvider->isLocalizable($assetFamilyCode, $attributeCode)) {
            return $this->getByLocale($assetFamilyCode, $attributeCode, $attributeValues, $akeneoLocale);
        }

        throw new TranslationNotFoundException();
    }

    /**
     * @return mixed|null
     *
     * @throws MissingScopeException
     */
    private function getByScope(string $assetFamilyCode, string $attributeCode, array $attributeValues, string $scope)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope) {
                continue;
            }

            return $this->assetAttributeValueBuilderProvider->build($assetFamilyCode, $attributeCode, null, $scope, $attributeValue['data']);
        }

        throw new MissingScopeException();
    }

    /**
     * @return mixed|null
     *
     * @throws MissingLocaleTranslationOrScopeException
     */
    private function getByLocaleAndScope(
        string $assetFamilyCode,
        string $attributeCode,
        array $attributeValues,
        string $locale,
        string $scope,
    ) {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope || $attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->assetAttributeValueBuilderProvider->build($assetFamilyCode, $attributeCode, $locale, $scope, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationOrScopeException();
    }

    /**
     * @return mixed|null
     *
     * @throws MissingLocaleTranslationException
     */
    private function getByLocale(string $assetFamilyCode, string $attributeCode, array $attributeValues, string $locale)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->assetAttributeValueBuilderProvider->build($assetFamilyCode, $attributeCode, $locale, null, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationException();
    }
}
