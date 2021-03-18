<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Provider\ProductRefEntityAttributeValueValueBuilderProviderInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\ReferenceEntityAttributeTypeMatcher;

final class ReferenceEntityAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertiesProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    /** @var \Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface */
    private $client;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributeDataProvider */
    private $akeneoReferenceEntityAttributeDataProvider;

    /** @var \Psr\Log\LoggerInterface */
    private $akeneoLogger;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ProductRefEntityAttributeValueValueBuilderProviderInterface */
    private $productRefEntityAttributeValueValueBuilderProvider;

    public function __construct(
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        AkeneoReferenceEntityAttributeDataProvider $akeneoReferenceEntityAttributeDataProvider,
        AttributeTypeMatcher $attributeTypeMatcher,
        AkeneoPimEnterpriseClientInterface $client,
        LoggerInterface $akeneoLogger,
        ProductRefEntityAttributeValueValueBuilderProviderInterface $productRefEntityAttributeValueValueBuilderProvider
    ) {
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->akeneoReferenceEntityAttributeDataProvider = $akeneoReferenceEntityAttributeDataProvider;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->client = $client;
        $this->akeneoLogger = $akeneoLogger;
        $this->productRefEntityAttributeValueValueBuilderProvider = $productRefEntityAttributeValueValueBuilderProvider;
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
            throw new \LogicException(sprintf('Locale and Scope are mandatory for %s reference entity.', $attributeCode));
        }

        $subAttributeValues = [];
        $referenceEntityAttributeProperties = $this->akeneoAttributePropertiesProvider->getProperties($attributeCode);
        $records = $this->client->getReferenceEntityRecordApi()->get(
            $referenceEntityAttributeProperties['reference_data_name'],
            $value
        );

        foreach ($records['values'] as $subAttributeCode => $attributeValue) {
            try {
                $data = $this->akeneoReferenceEntityAttributeDataProvider->getData(
                    $referenceEntityAttributeProperties['reference_data_name'],
                    $subAttributeCode,
                    $attributeValue,
                    $locale,
                    $scope
                );

                $dataProcessor = $this->productRefEntityAttributeValueValueBuilderProvider->getProcessor(
                    $attributeCode,
                    $referenceEntityAttributeProperties['reference_data_name'],
                    $subAttributeCode,
                    $locale,
                    $scope,
                    $data
                );

                $subAttributeValues[$subAttributeCode] = $dataProcessor->getValue(
                    $attributeCode,
                    $referenceEntityAttributeProperties['reference_data_name'],
                    $subAttributeCode,
                    $locale,
                    $scope,
                    $data
                );
            } catch (
                MissingLocaleTranslationException |
                MissingLocaleTranslationOrScopeException |
                MissingScopeException |
                TranslationNotFoundException $exception
            ) {
                $this->akeneoLogger->debug(\sprintf(
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
