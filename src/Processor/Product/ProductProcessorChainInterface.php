<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Sylius\Component\Core\Model\ProductInterface;

interface ProductProcessorChainInterface
{
    public function chain(ProductInterface $product, array $resource): void;
}
