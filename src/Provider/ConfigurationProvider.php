<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Exception;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

final class ConfigurationProvider
{
    private RepositoryInterface $apiConfigurationRepository;

    private ?ApiConfiguration $configuration = null;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function getConfiguration(): ApiConfiguration
    {
        if ($this->configuration instanceof ApiConfiguration) {
            return $this->configuration;
        }

        $this->configuration = $this->apiConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if (!$this->configuration instanceof ApiConfiguration) {
            throw new Exception('The API is not configured in the admin section.');
        }

        return $this->configuration;
    }
}
