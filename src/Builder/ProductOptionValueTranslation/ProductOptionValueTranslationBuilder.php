<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValueTranslation;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\Builder\ProductOptionValueTranslation\ProductOptionValueTranslationBuilderNotFoundException;

class ProductOptionValueTranslationBuilder implements ProductOptionValueTranslationBuilderProcessorInterface
{
    public function __construct(
        /** @var iterable<ProductOptionValueTranslationBuilderInterface> $productOptionValueTranslationBuilders */
        #[AutowireIterator(ProductOptionValueTranslationBuilderInterface::TAG_ID)]
        private iterable $productOptionValueTranslationBuilders,
    ) {
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
