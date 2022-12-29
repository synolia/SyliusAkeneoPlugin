<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValueTranslation;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Builder\ProductOptionValueTranslation\ProductOptionValueTranslationBuilderNotFoundException;
use Traversable;

class ProductOptionValueTranslationBuilder implements ProductOptionValueTranslationBuilderProcessorInterface
{
    /** @var array<ProductOptionValueTranslationBuilderInterface> */
    private array $productOptionValueTranslationBuilders;

    public function __construct(Traversable $handlers)
    {
        $this->productOptionValueTranslationBuilders = iterator_to_array($handlers);
    }

    /**
     * @inheritDoc
     */
    public function build(
        ProductOptionInterface $productOption,
        ProductOptionValueInterface $productOptionValue,
        string $locale,
        array $attributeValues,
    ): ProductOptionValueTranslationInterface {
        foreach ($this->productOptionValueTranslationBuilders as $builder) {
            if (!$builder->support($productOption, $productOptionValue, $locale, $attributeValues)) {
                continue;
            }

            return $builder->build($productOption, $productOptionValue, $locale, $attributeValues);
        }

        throw new ProductOptionValueTranslationBuilderNotFoundException();
    }
}
