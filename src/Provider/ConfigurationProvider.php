<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Exception;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

final class ConfigurationProvider
{
    private ?ApiConfiguration $configuration = null;

    public function __construct(private RepositoryInterface $apiConfigurationRepository)
    {
    }

    public function getConfiguration(): ApiConfiguration
    {
        if ($this->configuration instanceof ApiConfiguration) {
            return $this->configuration;
        }

        $configuration = $this->apiConfigurationRepository->findOneBy([], ['id' => 'DESC']);
        if (!$configuration instanceof ApiConfiguration) {
            throw new Exception('The API is not configured in the admin section.');
        }

        $this->configuration = $configuration;

        return $this->configuration;
    }
}
