<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;

final class AkeneoAttributeDataProvider
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertyProvider;

    public function __construct(AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider)
    {
        $this->akeneoAttributePropertyProvider = $akeneoAttributePropertyProvider;
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
            return $this->transformResponse($attributeValues[0]['data']);
        }

        if ($this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            !$this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->getByScope($attributeValues, $scope);
        }

        if ($this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            $this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->getByLocaleAndScope($attributeValues, $locale, $scope);
        }

        if (!$this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            $this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->getByLocale($attributeValues, $locale);
        }

        throw new TranslationNotFoundException();
    }

    private function getByLocaleAndScope(array $attributeValues, string $locale, string $scope): string
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope || $attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->transformResponse($attributeValue['data']);
        }

        throw new MissingLocaleTranslationOrScopeException();
    }

    private function getByScope(array $attributeValues, string $scope): string
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope) {
                continue;
            }

            return $this->transformResponse($attributeValue['data']);
        }

        throw new MissingScopeException();
    }

    private function getByLocale(array $attributeValues, string $locale): string
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->transformResponse($attributeValue['data']);
        }

        throw new MissingLocaleTranslationException();
    }

    /**
     * @param mixed $data
     */
    private function transformResponse($data): string
    {
        if (\is_array($data)) {
            return \trim(implode(' ', $data));
        }

        return \trim((string) $data);
    }
}
