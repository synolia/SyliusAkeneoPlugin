<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Builder\ProductOptionValue\ProductOptionValueBuilderNotFoundException;
use Traversable;

class ProductOptionValueBuilder implements ProductOptionValueBuilderInterface
{
    /** @var array<DynamicOptionValueBuilderInterface> */
    private array $dynamicProductOptionValueBuilders;

    public function __construct(Traversable $handlers)
    {
        $this->dynamicProductOptionValueBuilders = iterator_to_array($handlers);
    }

    public function build(ProductOptionInterface $productOption, $values): ProductOptionValueInterface
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
