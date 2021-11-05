<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;

interface ProductFilterRulesProviderInterface
{
    public function getProductFiltersRules(): ProductFiltersRules;
}
