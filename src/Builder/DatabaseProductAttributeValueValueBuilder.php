<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder;

use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\DatabaseMappingAttributeTypeMatcher;

final class DatabaseProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertiesProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
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

    public function build($value)
    {
        $attributeType = $this->attributeTypeMatcher->match($this->databaseMappingAttributeTypeMatcher->getType());
        $builder = $this->productAttributeValueValueBuilder->findBuilderByClassName($attributeType->getBuilder());

        return $builder->build($value);
    }
}
