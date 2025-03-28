<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class ClientFactory implements ClientFactoryInterface
{
    private ?AkeneoPimClientInterface $akeneoClient = null;

    public function __construct(private ApiConnectionProviderInterface $apiConnectionProvider)
    {
    }

    public function createFromApiCredentials(): AkeneoPimClientInterface
    {
        if ($this->akeneoClient instanceof AkeneoPimClientInterface) {
            return $this->akeneoClient;
        }

        $apiConnection = $this->apiConnectionProvider->get();

        $client = new AkeneoPimClientBuilder($apiConnection->getBaseUrl());

        $this->akeneoClient = $client->buildAuthenticatedByPassword(
            $apiConnection->getApiClientId(),
            $apiConnection->getApiClientSecret(),
            $apiConnection->getUsername(),
            $apiConnection->getPassword(),
        );

        return $this->akeneoClient;
    }

    /** @deprecated To be removed in 4.0. */
    public function authenticateByPassword(ApiConfiguration $apiConfiguration): AkeneoPimClientInterface
    {
        $client = new AkeneoPimClientBuilder($apiConfiguration->getBaseUrl() ?? '');

        return $client->buildAuthenticatedByPassword(
            $apiConfiguration->getApiClientId() ?? '',
            $apiConfiguration->getApiClientSecret() ?? '',
            $apiConfiguration->getUsername() ?? '',
            $apiConfiguration->getPassword() ?? '',
        );
    }
}
