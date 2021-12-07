<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;

interface AkeneoAttributeDataProviderInterface
{
    /**
     * @param mixed $attributeValues
     *
     * @throws MissingLocaleTranslationOrScopeException
     * @throws MissingScopeException
     * @throws MissingLocaleTranslationException
     *
     * @return mixed|null
     */
    public function getData(string $attributeCode, $attributeValues, string $locale, string $scope);
}
