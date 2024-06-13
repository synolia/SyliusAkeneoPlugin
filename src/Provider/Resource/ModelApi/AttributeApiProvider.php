<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Resource\ModelApi;

use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

class AttributeApiProvider implements ModelApiInterface
{
    public function support(PayloadInterface $payload): bool
    {
        return $payload instanceof AttributePayload;
    }

    public function get(PayloadInterface $payload): ListableResourceInterface
    {
        return $payload->getAkeneoPimClient()->getAttributeApi();
    }
}
