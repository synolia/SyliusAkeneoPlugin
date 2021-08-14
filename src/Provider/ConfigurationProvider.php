<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

final class ConfigurationProvider
{
    /** @var RepositoryInterface */
    private $apiConfigurationRepository;

    /** @var ApiConfiguration|null */
    private $configuration;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function getConfiguration(): ApiConfiguration
    {
        if ($this->configuration instanceof ApiConfiguration) {
            return $this->configuration;
        }

        $this->configuration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$this->configuration instanceof ApiConfiguration) {
            throw new \Exception('The API is not configured in the admin section.');
        }

        return $this->configuration;
    }
}
