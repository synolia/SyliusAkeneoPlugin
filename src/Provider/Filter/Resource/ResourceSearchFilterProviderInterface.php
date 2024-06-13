<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Filter\Resource;

use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

interface ResourceSearchFilterProviderInterface
{
    public function support(PayloadInterface $payload): bool;

    public function get(PayloadInterface $payload): array;
}
