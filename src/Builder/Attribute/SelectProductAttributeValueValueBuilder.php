<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AttributeOptionValueDataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\SelectAttributeTypeMatcher;

final class SelectProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    public function __construct(
        private AkeneoAttributePropertiesProviderInterface $akeneoAttributePropertiesProvider,
        private AttributeTypeMatcher $attributeTypeMatcher,
        private AttributeOptionValueDataTransformerInterface $attributeOptionValueDataTransformer,
    ) {
    }

    public function support(string $attributeCode): bool
    {
        return $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode)) instanceof SelectAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value)
    {
        return [$this->attributeOptionValueDataTransformer->transform($value)];
    }
}
