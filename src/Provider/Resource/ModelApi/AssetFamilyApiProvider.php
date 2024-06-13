<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Resource\ModelApi;

use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Asset\AssetPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

class AssetFamilyApiProvider implements ModelApiInterface
{
    public function support(PayloadInterface $payload): bool
    {
        return $payload instanceof AssetPayload;
    }

    public function get(PayloadInterface $payload): ListableResourceInterface
    {
        return $payload->getAkeneoPimClient()->getAssetFamilyApi();
    }
}
