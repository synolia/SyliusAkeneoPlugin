<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Client;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

final class ClientFactory
{
    /** @var string */
    private $mockServerHost;

    /** @var int */
    private $mockServerPort;

    public function __construct(string $mockServerHost, int $mockServerPort)
    {
        $this->mockServerHost = $mockServerHost;
        $this->mockServerPort = $mockServerPort;
    }

    public function createFromApiCredentials(): AkeneoPimEnterpriseClientInterface
    {
        $clientBuilder = new AkeneoPimEnterpriseClientBuilder(sprintf(
            'http://%s:%d',
            $this->mockServerHost,
            $this->mockServerPort
        ));

        return $clientBuilder->buildAuthenticatedByPassword(
            'client_id',
            'secret',
            'username',
            'password'
        );
    }
}
