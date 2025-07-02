<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface AssetAttributeValueBuilderInterface
{
    public function support(string $assetFamilyCode, string $attributeCode): bool;

    /**
     * @return mixed
     */
    public function build(string $assetFamilyCode, string $assetCode, ?string $locale, ?string $scope, mixed $value);
}
