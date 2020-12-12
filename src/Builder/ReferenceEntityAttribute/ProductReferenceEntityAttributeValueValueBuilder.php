<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute;

final class ProductReferenceEntityAttributeValueValueBuilder
{
    /** @var array<ProductReferenceEntityAttributeValueValueBuilderInterface> */
    private $referenceEntityAttributeValueBuilders;

    public function addBuilder(ProductReferenceEntityAttributeValueValueBuilderInterface $attributeValueBuilder): void
    {
        $this->referenceEntityAttributeValueBuilders[\get_class($attributeValueBuilder)] = $attributeValueBuilder;
    }

    /**
     * @param mixed $value
     *
     * @return mixed|null
     */
    public function build(string $referenceEntityCode, string $subAttributeCode, $value)
    {
        /** @var ProductReferenceEntityAttributeValueValueBuilderInterface $referenceEntityAttributeValueBuilder */
        foreach ($this->referenceEntityAttributeValueBuilders as $referenceEntityAttributeValueBuilder) {
            if ($referenceEntityAttributeValueBuilder->support($referenceEntityCode, $subAttributeCode)) {
                return $referenceEntityAttributeValueBuilder->build($value);
            }
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function findBuilderByClassName(string $className)
    {
        /** @var ProductReferenceEntityAttributeValueValueBuilderInterface $referenceEntityAttributeValueBuilder */
        foreach ($this->referenceEntityAttributeValueBuilders as $referenceEntityAttributeValueBuilder) {
            if (!$referenceEntityAttributeValueBuilder instanceof $className) {
                continue;
            }

            return $referenceEntityAttributeValueBuilder;
        }

        return null;
    }

    public function hasSupportedBuilder(string $referenceEntityCode, string $subAttributeCode): bool
    {
        foreach ($this->referenceEntityAttributeValueBuilders as $referenceEntityAttributeValueBuilder) {
            if ($referenceEntityAttributeValueBuilder->support($referenceEntityCode, $subAttributeCode)) {
                return true;
            }
        }

        return false;
    }
}
