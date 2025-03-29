<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute;

use Sylius\Component\Attribute\AttributeType\AttributeTypeInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;

#[AutoconfigureTag]
interface TaxonAttributeTypeMatcherInterface extends TypeMatcherInterface
{
    public function getAttributeType(): AttributeTypeInterface;
}
