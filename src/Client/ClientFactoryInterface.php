<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

interface ClientFactoryInterface
{
    public function createFromApiCredentials(): AkeneoPimClientInterface;

    /** @deprecated To be removed in 4.0. */
    public function authenticateByPassword(ApiConfiguration $apiConfiguration): AkeneoPimClientInterface;
}
