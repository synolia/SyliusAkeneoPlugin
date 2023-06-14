<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class ClientFactory implements ClientFactoryInterface
{
    private ?AkeneoPimClientInterface $akeneoClient = null;

    public function __construct(
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private string $cacheDir,
    ) {
    }

    public function createFromApiCredentials(): AkeneoPimClientInterface
    {
        if (null !== $this->akeneoClient) {
            return $this->akeneoClient;
        }

        $apiConnection = $this->apiConnectionProvider->get();

        $client = new AkeneoPimClientBuilder($apiConnection->getBaseUrl());

        $httpClient = HttpClient::create();

        $path = $this->cacheDir . '/akeneo';

        if (is_dir($path) === false) {
            mkdir($path);
        }

        $store = new Store($path);
        $httpClient = new CachingHttpClient($httpClient, $store, ['default_ttl' => 3600, 'allow_revalidate' => true, 'debug' => getenv('APP_DEBUG')]);

        $client->setHttpClient(new HttplugClient($httpClient));

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
