<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Component\Cache;

class CacheKey
{
    public const FAMILIES = 'akeneo_families';

    public const FAMILY = 'akeneo_family_%s';

    public const FAMILY_BY_VARIANT_CODE = 'akeneo_family_by_variant_code_%s';

    public const FAMILY_VARIANTS = 'akeneo_family_variants_%s';

    public const ATTRIBUTES = 'akeneo_attributes';
}
