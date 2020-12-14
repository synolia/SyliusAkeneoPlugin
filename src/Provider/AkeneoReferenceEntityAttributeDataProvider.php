<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute\ProductReferenceEntityAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;

final class AkeneoReferenceEntityAttributeDataProvider
{
    private AkeneoReferenceEntityAttributePropertiesProvider $akeneoReferenceEntityAttributePropertiesProvider;

    private ProductReferenceEntityAttributeValueValueBuilder $productReferenceEntityAttributeValueValueBuilder;

    public function __construct(
        AkeneoReferenceEntityAttributePropertiesProvider $akeneoReferenceEntityAttributePropertiesProvider,
        ProductReferenceEntityAttributeValueValueBuilder $productReferenceEntityAttributeValueValueBuilder
    ) {
        $this->akeneoReferenceEntityAttributePropertiesProvider = $akeneoReferenceEntityAttributePropertiesProvider;
        $this->productReferenceEntityAttributeValueValueBuilder = $productReferenceEntityAttributeValueValueBuilder;
    }

    /**
     * @param mixed $attributeValues
     *
     * @return mixed|null
     *
     * @throws MissingLocaleTranslationOrScopeException
     * @throws MissingScopeException
     * @throws MissingLocaleTranslationException
     */
    public function getData(string $referenceEntityCode, string $referenceEntityAttributeCode, $attributeValues, string $locale, string $scope)
    {
        if ($this->akeneoReferenceEntityAttributePropertiesProvider->isUnique($referenceEntityCode, $referenceEntityAttributeCode) ||
            (!$this->akeneoReferenceEntityAttributePropertiesProvider->isScopable($referenceEntityCode, $referenceEntityAttributeCode) &&
                !$this->akeneoReferenceEntityAttributePropertiesProvider->isLocalizable($referenceEntityCode, $referenceEntityAttributeCode))) {
            return $this->productReferenceEntityAttributeValueValueBuilder->build($referenceEntityCode, $referenceEntityAttributeCode, $attributeValues[0]['data']);
        }

        if ($this->akeneoReferenceEntityAttributePropertiesProvider->isScopable($referenceEntityCode, $referenceEntityAttributeCode) &&
            !$this->akeneoReferenceEntityAttributePropertiesProvider->isLocalizable($referenceEntityCode, $referenceEntityAttributeCode)) {
            return $this->getByScope($referenceEntityCode, $referenceEntityAttributeCode, $attributeValues, $scope);
        }

        if ($this->akeneoReferenceEntityAttributePropertiesProvider->isScopable($referenceEntityCode, $referenceEntityAttributeCode) &&
            $this->akeneoReferenceEntityAttributePropertiesProvider->isLocalizable($referenceEntityCode, $referenceEntityAttributeCode)) {
            return $this->getByLocaleAndScope($referenceEntityCode, $referenceEntityAttributeCode, $attributeValues, $locale, $scope);
        }

        if (!$this->akeneoReferenceEntityAttributePropertiesProvider->isScopable($referenceEntityCode, $referenceEntityAttributeCode) &&
            $this->akeneoReferenceEntityAttributePropertiesProvider->isLocalizable($referenceEntityCode, $referenceEntityAttributeCode)) {
            return $this->getByLocale($referenceEntityCode, $referenceEntityAttributeCode, $attributeValues, $locale);
        }

        throw new TranslationNotFoundException();
    }

    /**
     * @return mixed|null
     */
    private function getByScope(string $referenceEntityCode, string $referenceEntityAttributeCode, array $attributeValues, string $scope)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope) {
                continue;
            }

            return $this->productReferenceEntityAttributeValueValueBuilder->build($referenceEntityCode, $referenceEntityAttributeCode, $attributeValue['data']);
        }

        throw new MissingScopeException();
    }

    /**
     * @return mixed|null
     */
    private function getByLocaleAndScope(string $referenceEntityCode, string $referenceEntityAttributeCode, array $attributeValues, string $locale, string $scope)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope || $attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->productReferenceEntityAttributeValueValueBuilder->build($referenceEntityCode, $referenceEntityAttributeCode, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationOrScopeException();
    }

    /**
     * @return mixed|null
     */
    private function getByLocale(string $referenceEntityCode, string $referenceEntityAttributeCode, array $attributeValues, string $locale)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->productReferenceEntityAttributeValueValueBuilder->build($referenceEntityCode, $referenceEntityAttributeCode, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationException();
    }
}
