<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Asset\Attribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;

#[AutoconfigureTag(self::TAG_ID)]
interface AssetAttributeTypeMatcherInterface extends TypeMatcherInterface
{
    public const TAG_ID = 'sylius.akeneo.asset_attribute_type_matcher';
}
