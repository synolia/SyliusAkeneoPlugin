<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param mixed $attributeValues
     *
     * @return string|array|bool
     *
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException
     */
    public function getData(string $attributeCode, $attributeValues, string $locale, string $scope, ?string $attributeType = null)
    {
        $data = $attributeValues[0]['data'];

        if ($attributeType === SelectAttributeType::TYPE &&
            ($this->akeneoAttributePropertyProvider->isUnique($attributeCode) ||
            (!$this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            !$this->akeneoAttributePropertyProvider->isLocalizable($attributeCode))
        )) {
            return $this->transformArrayResponse($data);
        }

        if ($this->akeneoAttributePropertyProvider->isUnique($attributeCode) ||
            (!$this->akeneoAttributePropertyProvider->isScopable($attributeCode) &&
            !$this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)
        )) {
            return $this->transformResponse($data);
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

        if (is_bool($data)) {
            return $data;
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

    /**
     * @param array|string $datas
     */
    private function transformArrayResponse($datas): array
    {
        if (!is_array($datas)) {
            return [\trim((string) $datas)];
        }
        $transformedResponse = [];
        foreach ($datas as $data) {
            $transformedResponse[] = $data;
        }

        return $transformedResponse;
    }
}
