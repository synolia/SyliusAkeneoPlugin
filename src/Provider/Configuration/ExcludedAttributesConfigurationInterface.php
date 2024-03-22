<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration;

interface ExcludedAttributesConfigurationInterface
{
    /**
     * @return array<int, string>
     */
    public function get(): array;
}
