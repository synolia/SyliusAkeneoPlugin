<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class ClientFactory implements ClientFactoryInterface
{
    private ?AkeneoPimEnterpriseClientInterface $akeneoClient = null;

    private ApiConnectionProviderInterface $apiConnectionProvider;

    public function __construct(ApiConnectionProviderInterface $apiConnectionProvider)
    {
        $this->apiConnectionProvider = $apiConnectionProvider;
    }

    public function createFromApiCredentials(): AkeneoPimEnterpriseClientInterface
    {
        if (null !== $this->akeneoClient) {
            return $this->akeneoClient;
        }

        $apiConnection = $this->apiConnectionProvider->get();

        $client = new AkeneoPimEnterpriseClientBuilder($apiConnection->getBaseUrl());

        $this->akeneoClient = $client->buildAuthenticatedByPassword(
            $apiConnection->getApiClientId(),
            $apiConnection->getApiClientSecret(),
            $apiConnection->getUsername(),
            $apiConnection->getPassword(),
        );

        return $this->akeneoClient;
    }

    /** @deprecated To be removed in 4.0. */
    public function authenticateByPassword(ApiConfiguration $apiConfiguration): AkeneoPimEnterpriseClientInterface
    {
        $client = new AkeneoPimEnterpriseClientBuilder($apiConfiguration->getBaseUrl() ?? '');

        return $client->buildAuthenticatedByPassword(
            $apiConfiguration->getApiClientId() ?? '',
            $apiConfiguration->getApiClientSecret() ?? '',
            $apiConfiguration->getUsername() ?? '',
            $apiConfiguration->getPassword() ?? '',
        );
    }
}
