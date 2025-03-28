<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface ProductProcessorInterface
{
    public function process(ProductInterface $product, array $resource): void;

    public function support(ProductInterface $product, array $resource): bool;
}
