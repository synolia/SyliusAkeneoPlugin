<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute;

interface ProductReferenceEntityAttributeValueValueBuilderInterface
{
    public const TAG_ID = 'sylius.akeneo.reference_entity_attribute_value_value_builder';

    public function support(string $referenceEntityCode, string $subAttributeCode): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function build($value);
}
