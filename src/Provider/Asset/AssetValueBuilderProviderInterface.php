<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

interface AssetValueBuilderProviderInterface
{
    /**
     * @param mixed $value
     *
     * @return mixed|null
     */
    public function build(string $assetFamilyCode, string $assetCode, ?string $locale, ?string $scope, $value);

    public function hasSupportedBuilder(string $assetFamilyCode, string $assetCode): bool;
}
