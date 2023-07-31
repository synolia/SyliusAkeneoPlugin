<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Sylius\Component\Attribute\Model\AttributeInterface;

interface ProductAttributeTableProcessorInterface
{
    public function process(AttributeInterface $attribute, array $resource): void;
}
