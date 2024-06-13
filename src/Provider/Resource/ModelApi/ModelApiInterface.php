<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Resource\ModelApi;

use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

interface ModelApiInterface
{
    public function support(PayloadInterface $payload): bool;

    public function get(PayloadInterface $payload): ListableResourceInterface;
}
