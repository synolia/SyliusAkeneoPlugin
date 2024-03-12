<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration;

class ExcludedAttributesConfiguration implements ExcludedAttributesConfigurationInterface
{
    public function __construct(
        private array $excludedAttributeCodes = [],
    ) {
    }

    public function get(): array
    {
        return $this->excludedAttributeCodes;
    }
}
