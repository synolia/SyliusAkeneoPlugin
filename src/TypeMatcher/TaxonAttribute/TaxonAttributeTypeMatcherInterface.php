<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute;

use Sylius\Component\Attribute\AttributeType\AttributeTypeInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;

interface TaxonAttributeTypeMatcherInterface extends TypeMatcherInterface
{
    public const TAG_ID = 'sylius.akeneo.type_matcher.taxon.attribute';

    public function getAttributeType(): AttributeTypeInterface;
}
