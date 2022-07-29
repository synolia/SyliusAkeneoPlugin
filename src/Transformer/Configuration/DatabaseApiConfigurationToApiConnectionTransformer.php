<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer\Configuration;

use Synolia\SyliusAkeneoPlugin\Entity\ApiConfigurationInterface;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnection;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnectionInterface;

class DatabaseApiConfigurationToApiConnectionTransformer
{
    public function transform(ApiConfigurationInterface $configuration): ApiConnectionInterface
    {
        return new ApiConnection(
            $configuration->getBaseUrl() ?? '',
            $configuration->getUsername() ?? '',
            $configuration->getPassword() ?? '',
            $configuration->getApiClientId() ?? '',
            $configuration->getApiClientSecret() ?? '',
            $configuration->getEdition(),
            $configuration->getPaginationSize(),
        );
    }
}
