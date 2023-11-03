<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Model\Configuration;

interface CategoryConfigurationInterface
{
    public function getCategoryCodesToImport(): array;

    public function getCategoryCodesToExclude(): array;

    public function setCategoryCodesToImport(array $categoryCodesToImport): self;

    public function setCategoryCodesToExclude(array $categoryCodesToExclude): self;

    public function isUseAkeneoPositions(): bool;

    public function setUseAkeneoPositions(bool $useAkeneoPositions): self;
}
