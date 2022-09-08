<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

class EditionRetriever implements EditionRetrieverInterface
{
    private ApiConnectionProviderInterface $apiConnectionProvider;

    public function __construct(ApiConnectionProviderInterface $apiConnectionProvider)
    {
        $this->apiConnectionProvider = $apiConnectionProvider;
    }

    public function getEdition(): string
    {
        return $this->apiConnectionProvider->get()->getEdition();
    }
}
