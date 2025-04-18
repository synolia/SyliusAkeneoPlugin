<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: self::TAG_ID)]
interface ProductProcessorInterface
{
    public const TAG_ID = 'sylius.akeneo.product_processor';

    public function process(ProductInterface $product, array $resource): void;

    public function support(ProductInterface $product, array $resource): bool;
}
