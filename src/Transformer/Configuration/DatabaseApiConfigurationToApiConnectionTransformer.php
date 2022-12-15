<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer\Configuration;

use Synolia\SyliusAkeneoPlugin\Config\AkeneoAxesEnum;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnection;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnectionInterface;

class DatabaseApiConfigurationToApiConnectionTransformer
{
    public function transform(ApiConfiguration $configuration): ApiConnectionInterface
    {
        return new ApiConnection(
            $configuration->getBaseUrl() ?? '',
            $configuration->getUsername() ?? '',
            $configuration->getPassword() ?? '',
            $configuration->getApiClientId() ?? '',
            $configuration->getApiClientSecret() ?? '',
            $configuration->getEdition(),
            AkeneoAxesEnum::FIRST,
            $configuration->getPaginationSize(),
        );
    }
}
