<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

use Synolia\SyliusAkeneoPlugin\TypeMatcher\Asset\Attribute\AssetAttributeTypeMatcherInterface;

interface AssetAttributeTypeMatcherProviderInterface
{
    public function match(string $type): AssetAttributeTypeMatcherInterface;
}
