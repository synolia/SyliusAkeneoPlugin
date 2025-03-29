<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;

#[AutoconfigureTag]
interface AttributeTypeMatcherInterface extends TypeMatcherInterface
{
}
