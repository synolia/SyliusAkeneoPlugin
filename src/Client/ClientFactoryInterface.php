<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Client;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

interface ClientFactoryInterface
{
    public function createFromApiCredentials(): AkeneoPimEnterpriseClientInterface;

    public function authenticateByPassword(ApiConfiguration $apiConfiguration): AkeneoPimEnterpriseClientInterface;
}
