<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

final class ClientFactory implements ClientFactoryInterface
{
    private RepositoryInterface $apiConfigurationRepository;

    private ?AkeneoPimEnterpriseClientInterface $akeneoClient = null;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function createFromApiCredentials(): AkeneoPimEnterpriseClientInterface
    {
        if (null !== $this->akeneoClient) {
            return $this->akeneoClient;
        }

        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            throw new \Exception('The API is not configured in the admin section.');
        }

        $this->akeneoClient = $this->authenticateByPassword($apiConfiguration);

        return $this->akeneoClient;
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
