<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute;

use Webmozart\Assert\Assert;

final class ProductReferenceEntityAttributeValueValueBuilder
{
    /** @var array<ProductReferenceEntityAttributeValueValueBuilderInterface> */
    private ?array $referenceEntityAttributeValueBuilders = null;

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
        Assert::isIterable($this->referenceEntityAttributeValueBuilders);

        foreach ($this->referenceEntityAttributeValueBuilders as $referenceEntityAttributeValueBuilder) {
            $referenceEntityAttributeValueBuilderSupport = $referenceEntityAttributeValueBuilder->support($referenceEntityCode, $subAttributeCode);
            if ($referenceEntityAttributeValueBuilderSupport) {
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
        Assert::isIterable($this->referenceEntityAttributeValueBuilders);

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
        Assert::isIterable($this->referenceEntityAttributeValueBuilders);

        foreach ($this->referenceEntityAttributeValueBuilders as $referenceEntityAttributeValueBuilder) {
            $referenceEntityAttributeValueBuilderSupport = $referenceEntityAttributeValueBuilder->support($referenceEntityCode, $subAttributeCode);
            if ($referenceEntityAttributeValueBuilderSupport) {
                return true;
            }
        }

        return false;
    }
}
