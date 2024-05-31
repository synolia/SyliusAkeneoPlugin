<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\DatabaseMappingAttributeTypeMatcher;

final class DatabaseProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    public function __construct(
        private AkeneoAttributePropertiesProviderInterface $akeneoAttributePropertiesProvider,
        private AttributeTypeMatcher $attributeTypeMatcher,
        private DatabaseMappingAttributeTypeMatcher $databaseMappingAttributeTypeMatcher,
        private ProductAttributeValueValueBuilder $productAttributeValueValueBuilder,
    ) {
    }

    public function support(string $attributeCode): bool
    {
        return $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode)) instanceof DatabaseMappingAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value)
    {
        $attributeType = $this->attributeTypeMatcher->match($this->databaseMappingAttributeTypeMatcher->getType());
        $builder = $this->productAttributeValueValueBuilder->findBuilderByClassName($attributeType->getBuilder());

        return $builder->build($attributeCode, $locale, $scope, $value);
    }
}
