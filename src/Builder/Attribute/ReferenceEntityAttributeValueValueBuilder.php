<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoReferenceEntityAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ProductRefEntityAttributeValueValueBuilderProviderInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\ReferenceEntityAttributeTypeMatcher;
use Webmozart\Assert\Assert;

final class ReferenceEntityAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    public function __construct(
        private AkeneoAttributePropertiesProviderInterface $akeneoAttributePropertiesProvider,
        private AkeneoReferenceEntityAttributeDataProviderInterface $akeneoReferenceEntityAttributeDataProvider,
        private AttributeTypeMatcher $attributeTypeMatcher,
        private AkeneoPimClientInterface $client,
        private LoggerInterface $akeneoLogger,
        private ProductRefEntityAttributeValueValueBuilderProviderInterface $productRefEntityAttributeValueValueBuilderProvider,
    ) {
    }

    public function support(string $attributeCode): bool
    {
        return $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode)) instanceof ReferenceEntityAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value)
    {
        if (null === $locale || null === $scope) {
            throw new LogicException(sprintf('Locale and Scope are mandatory for %s reference entity.', $attributeCode));
        }

        Assert::string($value);

        $subAttributeValues = [];
        $referenceEntityAttributeProperties = $this->akeneoAttributePropertiesProvider->getProperties($attributeCode);
        $records = $this->client->getReferenceEntityRecordApi()->get(
            $referenceEntityAttributeProperties['reference_data_name'],
            $value,
        );

        foreach ($records['values'] as $subAttributeCode => $attributeValue) {
            try {
                $data = $this->akeneoReferenceEntityAttributeDataProvider->getData(
                    $referenceEntityAttributeProperties['reference_data_name'],
                    $subAttributeCode,
                    $attributeValue,
                    $locale,
                    $scope,
                );

                $dataProcessor = $this->productRefEntityAttributeValueValueBuilderProvider->getProcessor(
                    $attributeCode,
                    $referenceEntityAttributeProperties['reference_data_name'],
                    $subAttributeCode,
                    $locale,
                    $scope,
                    $data,
                );

                $subAttributeValues[$subAttributeCode] = $dataProcessor->getValue(
                    $attributeCode,
                    $referenceEntityAttributeProperties['reference_data_name'],
                    $subAttributeCode,
                    $locale,
                    $scope,
                    $data,
                );
            } catch (
                MissingLocaleTranslationException |
                MissingLocaleTranslationOrScopeException |
                MissingScopeException |
                TranslationNotFoundException
            ) {
                $this->akeneoLogger->debug(sprintf(
                    'Skipped attribute value "%s" for reference entity "%s" with value "%s" for locale "%s" and scope "%s"',
                    $subAttributeCode,
                    $referenceEntityAttributeProperties['reference_data_name'],
                    $value,
                    $locale,
                    $scope,
                ));
            }
        }

        return [
            'code' => $records['code'],
            'attributes' => $subAttributeValues,
        ];
    }
}
