<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer\Configuration;

use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\CategoryConfigurationInterface;

class DatabaseCategoryConfigurationToApiConnectionTransformer
{
    public function transform(CategoryConfiguration $configuration): CategoryConfigurationInterface
    {
        return new \Synolia\SyliusAkeneoPlugin\Model\Configuration\CategoryConfiguration(
            $configuration->getRootCategories(),
            $configuration->getNotImportCategories(),
        );
    }
}
