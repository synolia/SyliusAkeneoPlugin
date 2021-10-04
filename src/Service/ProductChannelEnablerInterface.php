<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Service;

use Sylius\Component\Core\Model\ProductInterface;

interface ProductChannelEnablerInterface
{
    public function enableChannelForProduct(ProductInterface $product, array $resource): void;
}
