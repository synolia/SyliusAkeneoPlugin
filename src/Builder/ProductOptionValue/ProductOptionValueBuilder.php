<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\Builder\ProductOptionValue\ProductOptionValueBuilderNotFoundException;

class ProductOptionValueBuilder implements ProductOptionValueBuilderInterface
{
    public function __construct(
        #[AutowireIterator(DynamicOptionValueBuilderInterface::class)]
        private iterable $dynamicProductOptionValueBuilders
    ) {
    }

    public function build(ProductOptionInterface $productOption, mixed $values): ProductOptionValueInterface
    {
        foreach ($this->dynamicProductOptionValueBuilders as $builder) {
            if (!$builder->support($productOption, $values)) {
                continue;
            }

            return $builder->build($productOption, $values);
        }

        throw new ProductOptionValueBuilderNotFoundException();
    }
}
