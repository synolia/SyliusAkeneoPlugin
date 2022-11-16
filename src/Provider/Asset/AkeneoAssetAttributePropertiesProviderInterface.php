<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

interface AkeneoAssetAttributePropertiesProviderInterface
{
    public function setLoadsAllAttributesAtOnce(bool $loadsAllAttributesAtOnce): self;

    public function isLocalizable(string $assetFamilyCode, string $attributeCode): bool;

    public function getProperties(string $assetFamilyCode, string $attributeCode): array;

    public function isScopable(string $assetFamilyCode, string $attributeCode): bool;

    public function getLabel(string $assetFamilyCode, string $attributeCode, ?string $locale): string;

    public function getLabels(string $assetFamilyCode, string $attributeCode): array;

    public function getType(string $assetFamilyCode, string $attributeCode): string;
}
