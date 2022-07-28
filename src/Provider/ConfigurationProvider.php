<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Exception;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfigurationInterface;

final class ConfigurationProvider
{
    private RepositoryInterface $apiConfigurationRepository;

    private ?ApiConfigurationInterface $configuration = null;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function getConfiguration(): ApiConfigurationInterface
    {
        if ($this->configuration instanceof ApiConfigurationInterface) {
            return $this->configuration;
        }

        $configuration = $this->apiConfigurationRepository->findOneBy([], ['id' => 'DESC']);
        if (!$configuration instanceof ApiConfigurationInterface) {
            throw new Exception('The API is not configured in the admin section.');
        }

        $this->configuration = $configuration;

        return $this->configuration;
    }
}
