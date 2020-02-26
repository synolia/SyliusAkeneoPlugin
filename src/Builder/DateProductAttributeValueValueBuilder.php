<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder;

use Sylius\Component\Product\Model\ProductAttributeValueInterface;

final class DateProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    public function support(string $attributeType): bool
    {
        return $attributeType === ProductAttributeValueInterface::STORAGE_DATE;
    }

    /**
     * {@inheritdoc}
     */
    public function build($value)
    {
        return \DateTime::createFromFormat(\DateTime::W3C, $value);
    }
}
