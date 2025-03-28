<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api;

use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\CategoryConfigurationInterface;
use Synolia\SyliusAkeneoPlugin\Repository\CategoryConfigurationRepository;
use Synolia\SyliusAkeneoPlugin\Transformer\Configuration\DatabaseCategoryConfigurationToApiConnectionTransformer;

class DatabaseCategoryConfigurationProvider implements CategoryConfigurationProviderInterface
{
    public static function getDefaultPriority(): int
    {
        return 0;
    }

    private ?CategoryConfigurationInterface $categoryConfiguration = null;

    public function __construct(
        private CategoryConfigurationRepository $categoriesConfigurationRepository,
        private DatabaseCategoryConfigurationToApiConnectionTransformer $databaseCategoryConfigurationToCategoryConfigurationTransformer,
    ) {
    }

    /**
     * @throws ApiNotConfiguredException
     */
    public function get(): CategoryConfigurationInterface
    {
        if ($this->categoryConfiguration instanceof CategoryConfigurationInterface) {
            return $this->categoryConfiguration;
        }

        $configuration = $this->categoriesConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if (!$configuration instanceof CategoryConfiguration) {
            throw new ApiNotConfiguredException();
        }

        return $this->categoryConfiguration = $this->databaseCategoryConfigurationToCategoryConfigurationTransformer->transform($configuration);
    }
}
