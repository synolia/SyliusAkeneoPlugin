<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\BooleanAttributeTypeMatcher;

final class BooleanProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    private AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider;

    private AttributeTypeMatcher $attributeTypeMatcher;

    public function __construct(
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        AttributeTypeMatcher $attributeTypeMatcher
    ) {
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
    }

    public function support(string $attributeCode): bool
    {
        return $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode)) instanceof BooleanAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value): bool
    {
        return (bool) $value;
    }
}
