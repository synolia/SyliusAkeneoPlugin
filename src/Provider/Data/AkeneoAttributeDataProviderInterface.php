<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Data;

use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;

interface AkeneoAttributeDataProviderInterface
{
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
    public function getData(string $attributeCode, $attributeValues, string $locale, string $scope);
}
