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
    public function build(string $attributeType, $value)
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilderInterface $attributeValueBuilder */
        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            if ($attributeValueBuilder->support($attributeType)) {
                return $attributeValueBuilder->build($value);
            }
        }

        return null;
    }

    public function hasSupportedBuilder(string $attributeType): bool
    {
        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            if ($attributeValueBuilder->support($attributeType)) {
                return true;
            }
        }

        return false;
    }
}
