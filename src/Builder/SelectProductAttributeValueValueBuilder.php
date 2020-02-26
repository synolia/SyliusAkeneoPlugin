<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder;

use Sylius\Component\Product\Model\ProductAttributeValueInterface;

final class SelectProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    public function support(string $attributeType): bool
    {
        return $attributeType === ProductAttributeValueInterface::STORAGE_JSON;
    }

    /**
     * {@inheritdoc}
     */
    public function build($value)
    {
        return $value;
    }
}
