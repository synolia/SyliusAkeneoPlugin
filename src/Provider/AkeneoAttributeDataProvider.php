<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;

final class AkeneoAttributeDataProvider implements AkeneoAttributeDataProviderInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertyProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder */
    private $productAttributeValueValueBuilder;

    public function __construct(
        AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider,
        ProductAttributeValueValueBuilder $productAttributeValueValueBuilder
    ) {
        $this->akeneoAttributePropertyProvider = $akeneoAttributePropertyProvider;
        $this->productAttributeValueValueBuilder = $productAttributeValueValueBuilder;
    }

    public function getData(string $attributeCode, $attributeValues, string $locale, string $scope)
    {
        if ($this->akeneoAttributePropertyProvider->isUnique($attributeCode) ||
            (!$this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
                !$this->akeneoAttributePropertyProvider->isLocalizable($attributeCode))) {
            return $this->productAttributeValueValueBuilder->build($attributeCode, $locale, $scope, $attributeValues[0]['data']);
        }

        if ($this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            !$this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->getByScope($attributeCode, $attributeValues, $scope);
        }

        if ($this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            $this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->getByLocaleAndScope($attributeCode, $attributeValues, $locale, $scope);
        }

        if (!$this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            $this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->getByLocale($attributeCode, $attributeValues, $locale);
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
