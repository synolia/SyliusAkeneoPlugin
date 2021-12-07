<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductOption;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Synolia\SyliusAkeneoPlugin\Manager\ProductOptionManager;

final class ProductOptionProcessor implements ProductOptionProcessorInterface
{
    private ProductOptionManager $productOptionManager;

    public function __construct(ProductOptionManager $productOptionManager)
    {
        $this->productOptionManager = $productOptionManager;
    }

    public function process(AttributeInterface $attribute, array $variationAxes = []): void
    {
        if (!\in_array($attribute->getCode(), $variationAxes, true)) {
            return;
        }

        $productOption = $this->productOptionManager->getProductOptionFromAttribute($attribute);

        if (null === $productOption) {
            $productOption = $this->productOptionManager->createProductOptionFromAttribute($attribute);
        }

        $this->productOptionManager->updateData($attribute, $productOption);
    }
}
