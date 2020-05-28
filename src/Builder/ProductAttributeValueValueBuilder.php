<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder;

final class ProductAttributeValueValueBuilder
{
    /** @var array<\Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilderInterface> */
    private $attributeValueBuilders;

    public function addBuilder(ProductAttributeValueValueBuilderInterface $attributeValueBuilder): void
    {
        $this->attributeValueBuilders[\get_class($attributeValueBuilder)] = $attributeValueBuilder;
    }

    /**
     * @param mixed $value
     *
     * @return mixed|null
     */
    public function build(string $attributeCode, $value)
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilderInterface $attributeValueBuilder */
        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            if ($attributeValueBuilder->support($attributeCode)) {
                return $attributeValueBuilder->build($value);
            }
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function findBuilderByClassName(string $className)
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilderInterface $attributeValueBuilder */
        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            if (!$attributeValueBuilder instanceof $className) {
                continue;
            }

            return $attributeValueBuilder;
        }

        return null;
    }

    public function hasSupportedBuilder(string $attributeCode): bool
    {
        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            if ($attributeValueBuilder->support($attributeCode)) {
                return true;
            }
        }

        return false;
    }
}
