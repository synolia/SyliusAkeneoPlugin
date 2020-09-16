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
    private const PAGING_SIZE = 1;

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

        $clientBuilder = new AkeneoPimEnterpriseClientBuilder($apiConfiguration->getBaseUrl() ?? '');

        $client = $clientBuilder->buildAuthenticatedByToken(
            $apiConfiguration->getApiClientId() ?? '',
            $apiConfiguration->getApiClientSecret() ?? '',
            $apiConfiguration->getToken() ?? '',
            $apiConfiguration->getRefreshToken() ?? '',
        );

        $this->updateApiconfigurationCredentials($client, $apiConfiguration);

        return $client;
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

    private function updateApiconfigurationCredentials(
        AkeneoPimEnterpriseClientInterface $client,
        ApiConfiguration $apiConfiguration
    ): void {
        try {
            $client->getCategoryApi()->all(self::PAGING_SIZE);
        } catch (HttpException $e) {
            $client = $this->authenticatedByPassword($apiConfiguration);

            $client->getCategoryApi()->all(self::PAGING_SIZE);
            $apiConfiguration->setToken($client->getToken() ?? '');
            $apiConfiguration->setRefreshToken($client->getRefreshToken() ?? '');

            if (!$this->entityManager instanceof EntityManager) {
                return;
            }
            $this->entityManager->flush($apiConfiguration);

            return;
        }
        if ($client->getToken() === $apiConfiguration->getToken()) {
            return;
        }

        $apiConfiguration->setToken($client->getToken() ?? '');
        $apiConfiguration->setRefreshToken($client->getRefreshToken() ?? '');

        if (!$this->entityManager instanceof EntityManager) {
            return;
        }
        $this->entityManager->flush($apiConfiguration);
    }
}
