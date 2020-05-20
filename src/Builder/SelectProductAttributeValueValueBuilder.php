<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder;

use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;

final class SelectProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    public function support(string $attributeType): bool
    {
        return $attributeType === ProductAttributeValueInterface::STORAGE_JSON || $attributeType === SelectAttributeType::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function build($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return [$value];
    }
}
