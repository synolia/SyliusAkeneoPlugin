<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Resource\ModelApi;

use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;

class ProductModelApiProvider implements ModelApiInterface
{
    public function support(PayloadInterface $payload): bool
    {
        return $payload instanceof ProductModelPayload;
    }

    public function get(PayloadInterface $payload): ListableResourceInterface
    {
        return $payload->getAkeneoPimClient()->getProductModelApi();
    }
}
