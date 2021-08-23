<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;

interface ProductConfigurationProviderInterface
{
    public function getProductConfiguration(): ?ProductConfiguration;
}
