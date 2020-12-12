<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute;

use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;

interface ReferenceEntityAttributeTypeMatcherInterface extends TypeMatcherInterface
{
    public const TAG_ID = 'sylius.akeneo.reference_entity_attribute_type_matcher';

    public function getStorageType(): string;
}
