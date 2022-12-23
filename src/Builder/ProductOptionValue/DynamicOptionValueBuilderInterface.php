<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;

interface DynamicOptionValueBuilderInterface
{
    public const TAG_ID = 'sylius.akeneo.dynamic_option_value_builder';

    public static function getDefaultPriority(): int;

    /**
     * @param mixed $values
     */
    public function support(ProductOptionInterface $productOption, $values, array $context = []): bool;

    /**
     * @param mixed $values
     */
    public function build(ProductOptionInterface $productOption, $values, array $context = []): ProductOptionValueInterface;
}
