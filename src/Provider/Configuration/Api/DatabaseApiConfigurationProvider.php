<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnectionInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\Configuration\DatabaseApiConfigurationToApiConnectionTransformer;

/**
 * @deprecated Will be removed in 4.0
 */
class DatabaseApiConfigurationProvider implements ApiConnectionProviderInterface
{
    public static function getDefaultPriority(): int
    {
        return 0;
    }

    private ?ApiConnectionInterface $apiConnection = null;

    public function __construct(
        private RepositoryInterface $apiConfigurationRepository,
        private DatabaseApiConfigurationToApiConnectionTransformer $databaseApiConfigurationToApiConnectionTransformer,
    ) {
    }

    /**
     * @throws ApiNotConfiguredException
     */
    public function get(): ApiConnectionInterface
    {
        if ($this->apiConnection instanceof ApiConnectionInterface) {
            return $this->apiConnection;
        }

        $configuration = $this->apiConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if (!$configuration instanceof ApiConfiguration) {
            throw new ApiNotConfiguredException();
        }

        return $this->apiConnection = $this->databaseApiConfigurationToApiConnectionTransformer->transform($configuration);
    }
}
