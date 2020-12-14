<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Exception;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

final class ClientFactory
{
    private RepositoryInterface $apiConfigurationRepository;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function createFromApiCredentials(): AkeneoPimEnterpriseClientInterface
    {
        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            throw new Exception('The API is not configured in the admin section.');
        }

        return $this->authenticateByPassword($apiConfiguration);
    }

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
