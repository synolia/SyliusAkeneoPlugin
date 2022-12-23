<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Builder\ProductOptionValue\ProductOptionValueBuilderNotFoundException;

interface ProductOptionValueBuilderInterface
{
    /**
     * @param mixed $values
     *
     * @throws ProductOptionValueBuilderNotFoundException
     */
    public function build(
        ProductOptionInterface $productOption,
        $values
    ): ProductOptionValueInterface;
}
