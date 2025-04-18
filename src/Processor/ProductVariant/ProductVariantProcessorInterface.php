<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: self::TAG_ID)]
interface ProductVariantProcessorInterface
{
    public const TAG_ID = 'sylius.akeneo.product_variant_processor';

    public function process(ProductVariantInterface $productVariant, array $resource): void;

    public function support(ProductVariantInterface $productVariant, array $resource): bool;
}
