<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductOptionValue;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface OptionValuesProcessorInterface
{
    public static function getDefaultPriority(): int;

    public function support(
        AttributeInterface $attribute,
        ProductOptionInterface $productOption,
        array $context = [],
    ): bool;

    public function process(
        AttributeInterface $attribute,
        ProductOptionInterface $productOption,
        array $context = [],
    ): void;
}
