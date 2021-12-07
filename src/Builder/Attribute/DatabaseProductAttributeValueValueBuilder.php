<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\DatabaseMappingAttributeTypeMatcher;

final class DatabaseProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    /** @var AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertiesProvider;

    /** @var AttributeTypeMatcher */
    private $attributeTypeMatcher;

    /** @var DatabaseMappingAttributeTypeMatcher */
    private $databaseMappingAttributeTypeMatcher;

    /** @var ProductAttributeValueValueBuilder */
    private $productAttributeValueValueBuilder;

    public function __construct(
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        AttributeTypeMatcher $attributeTypeMatcher,
        DatabaseMappingAttributeTypeMatcher $databaseMappingAttributeTypeMatcher,
        ProductAttributeValueValueBuilder $productAttributeValueValueBuilder
    ) {
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->databaseMappingAttributeTypeMatcher = $databaseMappingAttributeTypeMatcher;
        $this->productAttributeValueValueBuilder = $productAttributeValueValueBuilder;
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
