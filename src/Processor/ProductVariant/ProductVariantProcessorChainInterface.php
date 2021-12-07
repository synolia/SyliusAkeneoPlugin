<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Sylius\Component\Core\Model\ProductVariantInterface;

interface ProductVariantProcessorChainInterface
{
    public function chain(ProductVariantInterface $productVariant, array $resource): void;
}
