<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Data;

use Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

final class AkeneoAttributeDataProvider implements AkeneoAttributeDataProviderInterface
{
    public function __construct(
        private AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider,
        private ProductAttributeValueValueBuilder $productAttributeValueValueBuilder,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
    ) {
    }

    public function getData(string $attributeCode, $attributeValues, string $locale, string $scope)
    {
        $akeneoLocale = $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($locale);

        if ($this->akeneoAttributePropertyProvider->isUnique($attributeCode) ||
            (!$this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
                !$this->akeneoAttributePropertyProvider->isLocalizable($attributeCode))) {
            return $this->productAttributeValueValueBuilder->build($attributeCode, $akeneoLocale, $scope, $attributeValues[0]['data']);
        }

        if ($this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            !$this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->getByScope($attributeCode, $attributeValues, $scope);
        }

        if ($this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            $this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->getByLocaleAndScope($attributeCode, $attributeValues, $akeneoLocale, $scope);
        }

        if (!$this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            $this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->getByLocale($attributeCode, $attributeValues, $akeneoLocale);
        }

        throw new TranslationNotFoundException();
    }

    /**
     * @return mixed|null
     */
    private function getByScope(string $attributeCode, array $attributeValues, string $scope)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope) {
                continue;
            }

            return $this->productAttributeValueValueBuilder->build($attributeCode, null, $scope, $attributeValue['data']);
        }

        throw new MissingScopeException();
    }

    /**
     * @return mixed|null
     */
    private function getByLocaleAndScope(string $attributeCode, array $attributeValues, string $locale, string $scope)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope || $attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->productAttributeValueValueBuilder->build($attributeCode, $locale, $scope, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationOrScopeException();
    }

    /**
     * @return mixed|null
     */
    private function getByLocale(string $attributeCode, array $attributeValues, string $locale)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->productAttributeValueValueBuilder->build($attributeCode, $locale, null, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationException();
    }
}
