<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface ProductVariantProcessorInterface
{
    public function process(ProductVariantInterface $productVariant, array $resource): void;

    public function support(ProductVariantInterface $productVariant, array $resource): bool;
}
