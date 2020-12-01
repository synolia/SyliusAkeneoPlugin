<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute\ProductReferenceEntityAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributeDataProvider;
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

    /** @var \Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute\ProductReferenceEntityAttributeValueValueBuilder */
    private $referenceEntityAttributeValueValueBuilder;

    /** @var \Psr\Log\LoggerInterface */
    private $akeneoLogger;

    public function __construct(
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        AkeneoReferenceEntityAttributeDataProvider $akeneoReferenceEntityAttributeDataProvider,
        AttributeTypeMatcher $attributeTypeMatcher,
        AkeneoPimEnterpriseClientInterface $client,
        ProductReferenceEntityAttributeValueValueBuilder $referenceEntityAttributeValueValueBuilder,
        LoggerInterface $akeneoLogger
    ) {
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->akeneoReferenceEntityAttributeDataProvider = $akeneoReferenceEntityAttributeDataProvider;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->client = $client;
        $this->referenceEntityAttributeValueValueBuilder = $referenceEntityAttributeValueValueBuilder;
        $this->akeneoLogger = $akeneoLogger;
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
            throw new \LogicException('');
        }

        $subAttributeValues = [];
        $referenceEntityAttributeProperties = $this->akeneoAttributePropertiesProvider->getProperties($attributeCode);
        $records = $this->client->getReferenceEntityRecordApi()->get(
            $referenceEntityAttributeProperties['reference_data_name'],
            $value
        );

        foreach ($records['values'] as $attributeCode => $attributeValue) {
            try {
                $subAttributeValues[$attributeCode] = $this->akeneoReferenceEntityAttributeDataProvider->getData(
                    $referenceEntityAttributeProperties['reference_data_name'],
                    $attributeCode,
                    $attributeValue,
                    $locale,
                    $scope
                );
            } catch (
                MissingLocaleTranslationException |
                MissingLocaleTranslationOrScopeException |
                MissingScopeException |
                TranslationNotFoundException $exception
            ) {
                $this->akeneoLogger->warning(\sprintf(
                    'Skipped attribute value "%s" for reference entity "%s" with value "%s" for locale "%s" and scope "%s"',
                    $attributeCode,
                    $referenceEntityAttributeProperties['reference_data_name'],
                    $value,
                    $locale,
                    $scope,
                ), ['exception' => $exception]);
            }
        }

        return \json_encode([
            'code' => $records['code'],
            'values' => $subAttributeValues,
        ]);
    }
}
