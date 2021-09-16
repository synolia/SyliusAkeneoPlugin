<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer;

use Sylius\Component\Product\Model\ProductOptionInterface;

interface ProductOptionValueDataTransformerInterface
{
    public function transform(ProductOptionInterface $productOption, string $value): string;
}
