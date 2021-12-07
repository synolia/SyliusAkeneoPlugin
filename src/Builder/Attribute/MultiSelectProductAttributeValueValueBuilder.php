<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\AttributeOptionValueDataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\MultiSelectAttributeTypeMatcher;

final class MultiSelectProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    private AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider;

    private AttributeTypeMatcher $attributeTypeMatcher;

    private AttributeOptionValueDataTransformerInterface $attributeOptionValueDataTransformer;

    public function __construct(
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        AttributeTypeMatcher $attributeTypeMatcher,
        AttributeOptionValueDataTransformerInterface $attributeOptionValueDataTransformer
    ) {
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->attributeOptionValueDataTransformer = $attributeOptionValueDataTransformer;
    }

    public function support(string $attributeCode): bool
    {
        return $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode)) instanceof MultiSelectAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $values)
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->attributeOptionValueDataTransformer->transform($value);
        }

        return $values;
    }
}
