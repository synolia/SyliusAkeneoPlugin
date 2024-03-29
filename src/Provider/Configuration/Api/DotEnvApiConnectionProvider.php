<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api;

use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnection;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnectionInterface;

class DotEnvApiConnectionProvider implements ApiConnectionProviderInterface
{
    public function __construct(
        private string $baseUrl,
        private string $clientId,
        private string $clientSecret,
        private string $username,
        private string $password,
        private string $edition,
        private string $axeAsModel,
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
