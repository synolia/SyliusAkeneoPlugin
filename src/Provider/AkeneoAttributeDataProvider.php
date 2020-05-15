<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;

final class AkeneoAttributeDataProvider
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertyProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder */
    private $productAttributeValueValueBuilder;

    public function __construct(
        AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider,
        ProductAttributeValueValueBuilder $productAttributeValueValueBuilder
    ) {
        $this->akeneoAttributePropertyProvider = $akeneoAttributePropertyProvider;
        $this->productAttributeValueValueBuilder = $productAttributeValueValueBuilder;
    }

    /**
     * @param mixed $attributeValues
     *
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException
     */
    public function getData(string $attributeCode, $attributeValues, string $locale, string $scope): string
    {
        if ($this->akeneoAttributePropertyProvider->isUnique($attributeCode) ||
            (!$this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
                !$this->akeneoAttributePropertyProvider->isLocalizable($attributeCode))) {
            return $this->productAttributeValueValueBuilder->build($attributeCode, $attributeValues[0]['data']);
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

    private function getByScope(string $attributeCode, array $attributeValues, string $scope): string
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope) {
                continue;
            }

            return $this->productAttributeValueValueBuilder->build($attributeCode, $attributeValue['data']);
        }

        throw new MissingScopeException();
    }

    private function getByLocaleAndScope(string $attributeCode, array $attributeValues, string $locale, string $scope): string
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope || $attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->productAttributeValueValueBuilder->build($attributeCode, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationOrScopeException();
    }

    private function getByLocale(string $attributeCode, array $attributeValues, string $locale): string
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->productAttributeValueValueBuilder->build($attributeCode, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationException();
    }
}
