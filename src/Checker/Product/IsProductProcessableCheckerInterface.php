<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Checker\Product;

interface IsProductProcessableCheckerInterface
{
    public function check(array $resource): bool;
}
