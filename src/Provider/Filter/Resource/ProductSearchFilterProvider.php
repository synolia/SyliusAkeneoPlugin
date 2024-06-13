<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Filter\Resource;

use Synolia\SyliusAkeneoPlugin\Filter\ProductFilterInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;

class ProductSearchFilterProvider implements ResourceSearchFilterProviderInterface
{
    public function __construct(private ProductFilterInterface $productFilter)
    {
    }

    public function support(PayloadInterface $payload): bool
    {
        return $payload instanceof ProductPayload;
    }

    public function get(PayloadInterface $payload): array
    {
        $queryParameters = $this->productFilter->getProductFilters();
        $queryParameters['pagination_type'] = 'search_after';

        return $queryParameters;
    }
}
