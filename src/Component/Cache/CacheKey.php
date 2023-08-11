<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Component\Cache;

class CacheKey
{
    public const FAMILIES = 'akeneo:families';

    public const FAMILY = 'akeneo:family:%s';

    public const FAMILY_BY_VARIANT_CODE = 'akeneo:family_by_variant_code:%s';

    public const FAMILY_VARIANTS = 'akeneo:family_variants:%s';

    public const REFERENCE_ENTITY_ATTRIBUTES_PROPERTIES = 'akeneo:reference_entity_attributes:properties:%s';

    public const REFERENCE_ENTITY_ATTRIBUTES_PROPERTIES_UNIQUE = 'akeneo:reference_entity_attributes:properties:unique:%s:%s';

    public const REFERENCE_ENTITY_ATTRIBUTES_PROPERTIES_SCOPABLE = 'akeneo:reference_entity_attributes:properties:scopable:%s:%s';

    public const REFERENCE_ENTITY_ATTRIBUTES_PROPERTIES_LABEL = 'akeneo:reference_entity_attributes:properties:label:%s:%s';

    public const REFERENCE_ENTITY_ATTRIBUTES_PROPERTIES_LABELS = 'akeneo:reference_entity_attributes:properties:labels:%s:%s';

    public const REFERENCE_ENTITY_ATTRIBUTES_PROPERTIES_TYPE = 'akeneo:reference_entity_attributes:properties:type:%s:%s';

    public const REFERENCE_ENTITY_ATTRIBUTE_DATA = 'akeneo:reference_entity_attribute_data:%s:%s:%s_%s';

    public const ATTRIBUTES = 'akeneo:attributes';

    public const ATTRIBUTES_PROPERTIES = 'akeneo:attributes:properties:%s';

    public const ATTRIBUTES_LOCALIZABLE = 'akeneo:attributes:localizable:%s';

    public const ATTRIBUTES_UNIQUE = 'akeneo:attributes:unique:%s';

    public const ATTRIBUTES_SCOPABLE = 'akeneo:attributes:scopable:%s';

    public const ATTRIBUTES_LABEL = 'akeneo:attributes:label:%s_%s';

    public const ATTRIBUTES_LABELS = 'akeneo:attributes:labels:%s';

    public const ATTRIBUTES_TYPE = 'akeneo:attributes:type:%s';
}
