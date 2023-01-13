<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Asset\Attribute;

interface AkeneoAssetAttributeProcessorInterface
{
    public function support(
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $assetAttributeResource = [],
    ): bool;

    public function process(
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $assetAttributeResource,
    ): void;
}
