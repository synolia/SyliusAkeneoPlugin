<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Resource;

use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Resource\ModelApi\ModelApiProviderInterface;

class ResourcesProvider implements ResourcesProviderInterface
{
    public function __construct(private ModelApiProviderInterface $modelApiProvider)
    {
    }

    public function get(PayloadInterface $payload): array
    {
        $modelApi = $this->modelApiProvider->get($payload);

        return [];
    }
}
