<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Filter\Resource;

use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;

class CategorySearchFilterProvider implements ResourceSearchFilterProviderInterface
{
    public function __construct(private CategoryConfigurationProviderInterface $categoryConfigurationProvider)
    {
    }

    public function support(PayloadInterface $payload): bool
    {
        return $payload instanceof CategoryPayload;
    }

    /**
     * {@inheritdoc}
     */
    public function get(PayloadInterface $payload): array
    {
        $queryParameters['with_enriched_attributes'] = true;

        if ($this->categoryConfigurationProvider->get()->useAkeneoPositions()) {
            $queryParameters['with_position'] = true;
        }

        return $queryParameters;
    }
}
