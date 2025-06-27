<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

interface ClientFactoryInterface
{
    public function createFromApiCredentials(): AkeneoPimClientInterface;
}
