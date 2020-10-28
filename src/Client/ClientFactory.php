<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\Pim\ApiClient\Exception\HttpException;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

final class ClientFactory
{
    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $apiConfigurationRepository;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, RepositoryInterface $apiConfigurationRepository)
    {
        $this->entityManager = $entityManager;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function createFromApiCredentials(): AkeneoPimEnterpriseClientInterface
    {
        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            throw new \Exception('The API is not configured in the admin section.');
        }

        return $this->authenticatedByPassword($apiConfiguration);
    }

    public function authenticatedByPassword(ApiConfiguration $apiConfiguration): AkeneoPimEnterpriseClientInterface
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
