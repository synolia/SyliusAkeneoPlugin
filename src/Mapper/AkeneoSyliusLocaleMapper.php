<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Mapper;

use Synolia\SyliusAkeneoPlugin\Provider\Configuration\LocaleMappingConfigurationInterface;

class AkeneoSyliusLocaleMapper implements AkeneoSyliusLocaleMapperInterface
{
    public function __construct(private LocaleMappingConfigurationInterface $localeMappingConfiguration)
    {
    }

    public function map(string $akeneoLocale): array
    {
        $localeMapping = $this->localeMappingConfiguration->get();

        if (!array_key_exists($akeneoLocale, $localeMapping) || [] === $localeMapping[$akeneoLocale]) {
            return [$akeneoLocale];
        }

        return $localeMapping[$akeneoLocale];
    }

    /**
     * @throws LocaleNotFoundException
     */
    public function unmap(string $syliusLocale): string
    {
        $localeMapping = $this->localeMappingConfiguration->get();

        foreach ($localeMapping as $akeneoLocale => $mapping) {
            if (!in_array($syliusLocale, $mapping)) {
                continue;
            }

            return $akeneoLocale;
        }

        return $syliusLocale;
    }
}
