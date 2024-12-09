<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Data;

use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

final class AkeneoReferenceEntityAttributeDataProvider implements AkeneoReferenceEntityAttributeDataProviderInterface
{
    public function __construct(
        private AkeneoReferenceEntityAttributePropertiesProviderInterface $akeneoReferenceEntityAttributePropertiesProvider,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
    ) {
    }

    /**
     * @param mixed $attributeValues
     *
     * @throws MissingLocaleTranslationOrScopeException
     * @throws MissingScopeException
     * @throws MissingLocaleTranslationException
     * @throws TranslationNotFoundException
     *
     * @return mixed|null
     */
    public function getData(
        string $referenceEntityCode,
        string $referenceEntityAttributeCode,
        $attributeValues,
        string $locale,
        string $scope,
    ) {
        $akeneoLocale = $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($locale);

        if ($this->akeneoReferenceEntityAttributePropertiesProvider->isUnique($referenceEntityCode, $referenceEntityAttributeCode) ||
            (!$this->akeneoReferenceEntityAttributePropertiesProvider->isScopable($referenceEntityCode, $referenceEntityAttributeCode) &&
                !$this->akeneoReferenceEntityAttributePropertiesProvider->isLocalizable($referenceEntityCode, $referenceEntityAttributeCode))) {
            return $attributeValues[0]['data'];
        }

        if ($this->akeneoReferenceEntityAttributePropertiesProvider->isScopable($referenceEntityCode, $referenceEntityAttributeCode) &&
            !$this->akeneoReferenceEntityAttributePropertiesProvider->isLocalizable($referenceEntityCode, $referenceEntityAttributeCode)) {
            return $this->getByScope($attributeValues, $scope);
        }

        if ($this->akeneoReferenceEntityAttributePropertiesProvider->isScopable($referenceEntityCode, $referenceEntityAttributeCode) &&
            $this->akeneoReferenceEntityAttributePropertiesProvider->isLocalizable($referenceEntityCode, $referenceEntityAttributeCode)) {
            return $this->getByLocaleAndScope($attributeValues, $akeneoLocale, $scope);
        }

        if (!$this->akeneoReferenceEntityAttributePropertiesProvider->isScopable($referenceEntityCode, $referenceEntityAttributeCode) &&
            $this->akeneoReferenceEntityAttributePropertiesProvider->isLocalizable($referenceEntityCode, $referenceEntityAttributeCode)) {
            return $this->getByLocale($attributeValues, $akeneoLocale);
        }

        throw new TranslationNotFoundException();
    }

    /**
     * @return mixed|null
     */
    private function getByScope(
        array $attributeValues,
        string $scope,
    ) {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope) {
                continue;
            }

            return $attributeValue['data'];
        }

        throw new MissingScopeException();
    }

    /**
     * @return mixed|null
     */
    private function getByLocaleAndScope(
        array $attributeValues,
        string $locale,
        string $scope,
    ) {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope || $attributeValue['locale'] !== $locale) {
                continue;
            }

            return $attributeValue['data'];
        }

        throw new MissingLocaleTranslationOrScopeException();
    }

    /**
     * @return mixed|null
     */
    private function getByLocale(
        array $attributeValues,
        string $locale,
    ) {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['locale'] !== $locale) {
                continue;
            }

            return $attributeValue['data'];
        }

        throw new MissingLocaleTranslationException();
    }
}
