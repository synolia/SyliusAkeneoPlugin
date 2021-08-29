<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;

interface ProductOptionManagerInterface
{
    public function getProductOptionFromAttribute(AttributeInterface $attribute): ?ProductOptionInterface;

    public function createProductOptionFromAttribute(AttributeInterface $attribute): ProductOptionInterface;

    public function updateData(AttributeInterface $attribute, ProductOptionInterface $productOption): void;
}
