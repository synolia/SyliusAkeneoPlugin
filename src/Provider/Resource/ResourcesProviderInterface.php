<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Resource;

use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

interface ResourcesProviderInterface
{
    public function get(PayloadInterface $payload): array;
}
