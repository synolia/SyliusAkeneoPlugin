<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoProductAttributeValueProcessorException;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\ProductAttributeValue\TableAttributeValueProcessorProviderInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\TableAttributeTypeMatcher;

final class TableAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    public function __construct(
        private AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        private AttributeTypeMatcher $attributeTypeMatcher,
        private RepositoryInterface $productAttributeRepository,
        private TableAttributeValueProcessorProviderInterface $tableAttributeValueProcessorProvider,
    ) {
    }

    public function support(string $attributeCode): bool
    {
        return $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode)) instanceof TableAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $attribute = $this->productAttributeRepository->findOneBy(['code' => $attributeCode]);

        if (!$attribute instanceof ProductAttributeInterface) {
            return null;
        }

        if (!array_key_exists('table_configuration', $attribute->getConfiguration())) {
            return $value;
        }

        foreach ($value as $key => $row) {
            foreach ($row as $columnHeaderName => $cellValue) {
                foreach ($attribute->getConfiguration()['table_configuration'] as $tableConfiguration) {
                    if ($tableConfiguration['code'] !== $columnHeaderName) {
                        continue;
                    }

                    if (null === $locale) {
                        continue;
                    }

                    if (!array_key_exists($locale, $tableConfiguration['labels'])) {
                        continue;
                    }

                    try {
                        $processor = $this->tableAttributeValueProcessorProvider->getProcessor(
                            $attribute,
                            $tableConfiguration,
                            $locale,
                            $scope,
                            $value,
                        );

                        $cell = $processor->getValue($attribute, $tableConfiguration, $locale, $scope, $cellValue);

                        $value[$key][$columnHeaderName] = [
                            'label' => $tableConfiguration['labels'][$locale],
                            'value' => $cell,
                        ];
                    } catch (MissingAkeneoProductAttributeValueProcessorException) {
                    }
                }
            }
        }

        return $value;
    }
}
