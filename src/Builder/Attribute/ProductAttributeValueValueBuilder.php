<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Webmozart\Assert\Assert;

final class ProductAttributeValueValueBuilder
{
    /** @var array<ProductAttributeValueValueBuilderInterface> */
    private ?array $attributeValueBuilders = null;

    public function addBuilder(ProductAttributeValueValueBuilderInterface $attributeValueBuilder): void
    {
        $this->attributeValueBuilders[\get_class($attributeValueBuilder)] = $attributeValueBuilder;
    }

    /**
     * @param mixed $value
     *
     * @return mixed|null
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value)
    {
        Assert::isIterable($this->attributeValueBuilders);

        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            $attributeValueBuilderSupport = $attributeValueBuilder->support($attributeCode);
            if ($attributeValueBuilderSupport) {
                return $attributeValueBuilder->build($attributeCode, $locale, $scope, $value);
            }
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function findBuilderByClassName(string $className)
    {
        Assert::isIterable($this->attributeValueBuilders);

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
        Assert::isIterable($this->attributeValueBuilders);

        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            $attributeValueBuilderSupport = $attributeValueBuilder->support($attributeCode);
            if ($attributeValueBuilderSupport) {
                return true;
            }
        }

        return false;
    }
}
