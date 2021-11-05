<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductOption;

use Sylius\Component\Attribute\Model\AttributeInterface;

interface ProductOptionProcessorInterface
{
    public function process(AttributeInterface $attribute, array $variationAxes): void;
}
