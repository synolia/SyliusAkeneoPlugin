<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api;

use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnectionInterface;

interface ApiConnectionProviderInterface
{
    public const TAG_ID = 'sylius.akeneo.provider.api_connection';

    public function get(): ApiConnectionInterface;
}
