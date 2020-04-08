<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

final class ClientFactory
{
    private const PAGING_SIZE = 1;

    /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientBuilder */
    private $clientBuilder;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $apiConfigurationRepository;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, RepositoryInterface $apiConfigurationRepository)
    {
        $this->entityManager = $entityManager;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function createFromApiCredentials(): AkeneoPimClientInterface
    {
        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            throw new \Exception('The API is not configured in the admin section.');
        }

        $this->clientBuilder = new AkeneoPimClientBuilder($apiConfiguration->getBaseUrl() ?? '');

        /** @var AkeneoPimClientInterface $client */
        $client = $this->clientBuilder->buildAuthenticatedByToken(
            $apiConfiguration->getApiClientId() ?? '',
            $apiConfiguration->getApiClientSecret() ?? '',
            $apiConfiguration->getToken() ?? '',
            $apiConfiguration->getRefreshToken() ?? '',
        );

        $this->updateApiconfigurationCredentials($client, $apiConfiguration);

        return $client;
    }

    private function updateApiconfigurationCredentials(
        AkeneoPimClientInterface $client,
        ApiConfiguration $apiConfiguration
    ): void {
        $client->getCategoryApi()->all(self::PAGING_SIZE);
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
