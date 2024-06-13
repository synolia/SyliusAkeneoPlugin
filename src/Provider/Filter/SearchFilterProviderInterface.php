<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Filter;

use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

interface SearchFilterProviderInterface
{
    public function get(PayloadInterface $payload): array;
}
