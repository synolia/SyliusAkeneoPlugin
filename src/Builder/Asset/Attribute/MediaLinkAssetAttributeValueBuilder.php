<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute;

use Synolia\SyliusAkeneoPlugin\Provider\Asset\AkeneoAssetAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AssetAttributeTypeMatcherProviderInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Asset\Attribute\MediaLinkAssetAttributeTypeMatcher;

final class MediaLinkAssetAttributeValueBuilder implements AssetAttributeValueBuilderInterface
{
    private AssetAttributeTypeMatcherProviderInterface $assetAttributeTypeMatcherProvider;

    private AkeneoAssetAttributePropertiesProviderInterface $akeneoAssetAttributePropertiesProvider;

    public function __construct(
        AssetAttributeTypeMatcherProviderInterface $assetAttributeTypeMatcherProvider,
        AkeneoAssetAttributePropertiesProviderInterface $akeneoAssetAttributePropertiesProvider
    ) {
        $this->assetAttributeTypeMatcherProvider = $assetAttributeTypeMatcherProvider;
        $this->akeneoAssetAttributePropertiesProvider = $akeneoAssetAttributePropertiesProvider;
    }

    public function support(string $assetFamilyCode, string $attributeCode): bool
    {
        return $this->assetAttributeTypeMatcherProvider->match($this->akeneoAssetAttributePropertiesProvider->getType($assetFamilyCode, $attributeCode)) instanceof MediaLinkAssetAttributeTypeMatcher;
    }

    public function build(string $assetFamilyCode, string $assetCode, ?string $locale, ?string $scope, $value): array
    {
        return ['url' => $value];
    }
}
