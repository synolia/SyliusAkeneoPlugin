<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute;

use Synolia\SyliusAkeneoPlugin\Provider\Asset\AkeneoAssetAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AssetAttributeTypeMatcherProviderInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Asset\Attribute\TextAssetAttributeTypeMatcher;

final class TextAssetAttributeValueBuilder implements AssetAttributeValueBuilderInterface
{
    public function __construct(
        private AssetAttributeTypeMatcherProviderInterface $assetAttributeTypeMatcherProvider,
        private AkeneoAssetAttributePropertiesProviderInterface $akeneoAssetAttributePropertiesProvider,
    ) {
    }

    public function support(string $assetFamilyCode, string $attributeCode): bool
    {
        return $this->assetAttributeTypeMatcherProvider->match($this->akeneoAssetAttributePropertiesProvider->getType($assetFamilyCode, $attributeCode)) instanceof TextAssetAttributeTypeMatcher;
    }

    public function build(
        string $assetFamilyCode,
        string $assetCode,
        ?string $locale,
        ?string $scope,
        mixed $value,
    ): array {
        return ['value' => $value];
    }
}
