<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Resource\ModelApi;

use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

class CategoryApiProvider implements ModelApiInterface
{
    public function support(PayloadInterface $payload): bool
    {
        return $payload instanceof CategoryPayload;
    }

    public function get(PayloadInterface $payload): ListableResourceInterface
    {
        return $payload->getAkeneoPimClient()->getCategoryApi();
    }
}
