<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

interface AkeneoFamilyPropertiesProviderInterface
{
    public function getProperties(string $familyCode): array;
}
