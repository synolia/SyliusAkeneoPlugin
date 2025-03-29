<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface DynamicOptionValueBuilderInterface
{
    public static function getDefaultPriority(): int;

    public function support(ProductOptionInterface $productOption, mixed $values, array $context = []): bool;

    public function build(
        ProductOptionInterface $productOption,
        mixed $values,
        array $context = [],
    ): ProductOptionValueInterface;
}
