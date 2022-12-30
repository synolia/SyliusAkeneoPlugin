<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

final class ClientFactory
{
    public function __construct(private string $mockServerHost, private int $mockServerPort)
    {
    }

    public function createFromApiCredentials(): AkeneoPimClientInterface
    {
        $clientBuilder = new AkeneoPimClientBuilder(sprintf(
            'http://%s:%d',
            $this->mockServerHost,
            $this->mockServerPort,
        ));

        return $clientBuilder->buildAuthenticatedByPassword(
            'client_id',
            'secret',
            'username',
            'password',
        );
    }
}
