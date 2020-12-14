<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\TextareaAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\TextAttributeTypeMatcher;

final class TextProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
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
        $typeMatcher = $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode));

        return $typeMatcher instanceof TextAttributeTypeMatcher ||
            $typeMatcher instanceof TextareaAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value)
    {
        return \trim((string) $value);
    }
}
