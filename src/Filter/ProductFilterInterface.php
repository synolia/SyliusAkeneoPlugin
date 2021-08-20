<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Filter;

interface ProductFilterInterface
{
    public function getProductModelFilters(): array;

    public function getProductFilters(): array;
}
