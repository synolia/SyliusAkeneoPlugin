<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute;

final class JsonReferenceEntityAttributeValueValueBuilder implements ProductReferenceEntityAttributeValueValueBuilderInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function support(string $referenceEntityCode, string $subAttributeCode): bool
    {
        //No custom processing per reference entity attribute type for now.
        return true;
    }

    public function build($value)
    {
        return $value;
    }
}
