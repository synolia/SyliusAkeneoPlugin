<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValueTranslation;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Retriever\FamilyMeasureNotFoundException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Retriever\MeasurableNotFoundException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyMeasureRetriever;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\MetricAttributeTypeMatcher;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

class MetricProductOptionValueTranslationBuilder implements ProductOptionValueTranslationBuilderInterface
{
    private AttributeTypeMatcher $attributeTypeMatcher;

    private AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider;

    private AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider;

    private FamilyMeasureRetriever $measureFamilyRetriever;

    public function __construct(
        AttributeTypeMatcher $attributeTypeMatcher,
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        FamilyMeasureRetriever $measureFamilyRetriever
    ) {
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->measureFamilyRetriever = $measureFamilyRetriever;
    }

    public function support(
        ProductOptionInterface $productOption,
        ProductOptionValueInterface $productOptionValue,
        string $locale,
        array $attributeValues
    ): bool {
        try {
            $attributeCode = $productOption->getCode();
            Assert::string($attributeCode);

            return $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode)) instanceof MetricAttributeTypeMatcher;
        } catch (UnsupportedAttributeTypeException|InvalidArgumentException $unsupportedAttributeTypeException) {
            return false;
        }
    }

    /**
     * @throws MissingLocaleTranslationOrScopeException
     * @throws MeasurableNotFoundException
     * @throws FamilyMeasureNotFoundException
     * @throws MissingLocaleTranslationException
     * @throws MissingScopeException
     * @throws TranslationNotFoundException
     */
    public function build(
        ProductOptionInterface $productOption,
        ProductOptionValueInterface $productOptionValue,
        string $locale,
        array $attributeValues
    ): ProductOptionValueTranslationInterface {
        $attributeCode = $productOption->getCode();
        Assert::string($attributeCode);

        /** @var array{unit: string, amount: string} $data */
        $data = $this->akeneoAttributeDataProvider->getData(
            $attributeCode,
            $attributeValues,
            $locale,
            'ecommerce'
        );

        $productOptionValue->setCurrentLocale($locale);

        $properties = $this->akeneoAttributePropertiesProvider->getProperties($attributeCode);
        $measurable = $this->measureFamilyRetriever->getMeasurable($properties['metric_family'], $data['unit']);
        $productOptionValue->setValue(\sprintf('%s %s', (float) $data['amount'], $measurable['symbol']));

        return $productOptionValue->getTranslation($locale);
    }

    public static function getDefaultPriority(): int
    {
        return 100;
    }
}
