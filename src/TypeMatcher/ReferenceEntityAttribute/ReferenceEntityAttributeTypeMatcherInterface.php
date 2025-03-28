<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;

#[AutoconfigureTag]
interface ReferenceEntityAttributeTypeMatcherInterface extends TypeMatcherInterface
{
    public function getStorageType(): string;
}
