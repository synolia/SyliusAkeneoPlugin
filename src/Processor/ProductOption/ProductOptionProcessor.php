<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductOption;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Synolia\SyliusAkeneoPlugin\Manager\ProductOptionManagerInterface;

final class ProductOptionProcessor implements ProductOptionProcessorInterface
{
    public function __construct(private ProductOptionManagerInterface $productOptionManager)
    {
    }

    public function process(AttributeInterface $attribute, array $variationAxes = []): void
    {
        if (!\in_array($attribute->getCode(), $variationAxes, true)) {
            return;
        }

        $productOption = $this->productOptionManager->getProductOptionFromAttribute($attribute);

        if (!$productOption instanceof ProductOptionInterface) {
            $productOption = $this->productOptionManager->createProductOptionFromAttribute($attribute);
        }

        $this->productOptionManager->updateData($attribute, $productOption);
    }
}
