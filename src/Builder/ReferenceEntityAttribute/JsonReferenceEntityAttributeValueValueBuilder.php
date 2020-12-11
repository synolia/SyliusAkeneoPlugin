<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;

final class JsonReferenceEntityAttributeValueValueBuilder implements ProductReferenceEntityAttributeValueValueBuilderInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertiesProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributeDataProvider */
    private $akeneoReferenceEntityAttributeDataProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    /** @var \Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface */
    private $client;

    public function __construct(
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        AkeneoReferenceEntityAttributeDataProvider $akeneoReferenceEntityAttributeDataProvider,
        AttributeTypeMatcher $attributeTypeMatcher,
        AkeneoPimEnterpriseClientInterface $client
    ) {
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->akeneoReferenceEntityAttributeDataProvider = $akeneoReferenceEntityAttributeDataProvider;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->client = $client;
    }

    public function support(string $referenceEntityCode, string $subAttributeCode): bool
    {
        //No custom processing per reference entity attribute type for now.
        return true;
    }

    public function build($value)
    {
        return $value;
    }
}
