<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnection;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnectionInterface;

class DotEnvApiConnectionProvider implements ApiConnectionProviderInterface
{
    public function __construct(
        #[Autowire(param: 'env(string:SYNOLIA_AKENEO_BASE_URL)')]
        private string $baseUrl,
        #[Autowire(param: 'env(string:SYNOLIA_AKENEO_CLIENT_ID)')]
        private string $clientId,
        #[Autowire(param: 'env(string:SYNOLIA_AKENEO_CLIENT_SECRET)')]
        private string $clientSecret,
        #[Autowire(param: 'env(string:SYNOLIA_AKENEO_USERNAME)')]
        private string $username,
        #[Autowire(param: 'env(string:SYNOLIA_AKENEO_PASSWORD)')]
        private string $password,
        #[Autowire(param: 'env(string:SYNOLIA_AKENEO_EDITION)')]
        private string $edition,
        #[Autowire(param: 'env(string:SYNOLIA_AKENEO_AXE_AS_MODEL)')]
        private string $axeAsModel,
        #[Autowire(param: 'env(int:SYNOLIA_AKENEO_PAGINATION)')]
        private int $pagination,
    ) {
    }

    public function get(): ApiConnectionInterface
    {
        return new ApiConnection(
            $this->baseUrl,
            $this->username,
            $this->password,
            $this->clientId,
            $this->clientSecret,
            $this->edition,
            $this->axeAsModel,
            $this->pagination,
        );
    }
}
