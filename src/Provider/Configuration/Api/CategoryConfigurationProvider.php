<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api;

use Synolia\SyliusAkeneoPlugin\Model\Configuration\CategoryConfiguration;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\CategoryConfigurationInterface;

class CategoryConfigurationProvider implements CategoryConfigurationProviderInterface
{
    private ?CategoryConfigurationInterface $configuration = null;

    public function __construct(
        private array $categoryCodesToImport,
        private array $categoryCodesToExclude,
        private bool $useAkeneoPositions,
    ) {
    }

    public function get(): CategoryConfigurationInterface
    {
        if (null !== $this->configuration) {
            return $this->configuration;
        }

        return $this->configuration = new CategoryConfiguration(
            $this->categoryCodesToImport,
            $this->categoryCodesToExclude,
            $this->useAkeneoPositions,
        );
    }
}
