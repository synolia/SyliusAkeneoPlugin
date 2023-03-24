<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Model\Configuration;

interface CategoryConfigurationInterface
{
    public function getCategoryCodesToImport(): array;

    public function getCategoryCodesToExclude(): array;

    public function setCategoryCodesToImport(array $categoryCodesToImport): CategoryConfiguration;

    public function setCategoryCodesToExclude(array $categoryCodesToExclude): CategoryConfiguration;
}
