<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Sylius\Component\Core\Model\ProductInterface;

interface ImageManagerInterfce
{
    public function updateImages(array $resource, ProductInterface $product): void;
}
