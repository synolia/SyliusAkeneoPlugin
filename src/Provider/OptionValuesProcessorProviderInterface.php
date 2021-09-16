<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductOptionValue\OptionValuesProcessorInterface;

interface OptionValuesProcessorProviderInterface
{
    public function getProcessor(AttributeInterface $attribute, ProductOptionInterface $productOption, array $context = []): OptionValuesProcessorInterface;
}
