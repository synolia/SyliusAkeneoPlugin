<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Sylius\Component\Core\Model\ProductInterface;

interface AssetProductAttributeProcessorInterface
{
    public function support(
        ProductInterface $model,
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $assetAttributeResource = [],
    ): bool;

    public function process(
        ProductInterface $model,
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $assetAttributeResource,
    ): void;
}
