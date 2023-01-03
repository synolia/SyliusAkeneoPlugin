<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Client;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

final class ClientFactory
{
    public function __construct(private string $mockServerHost, private int $mockServerPort)
    {
    }

    public function createFromApiCredentials(): AkeneoPimEnterpriseClientInterface
    {
        $clientBuilder = new AkeneoPimEnterpriseClientBuilder(sprintf(
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
