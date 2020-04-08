<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

final class ConfigurationProvider
{
    /** @var RepositoryInterface */
    private $apiConfigurationRepository;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function getConfiguration(): ApiConfiguration
    {
        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            throw new \Exception('The API is not configured in the admin section.');
        }

        return $apiConfiguration;
    }
}
