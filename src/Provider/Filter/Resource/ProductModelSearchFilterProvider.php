<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Filter\Resource;

use Synolia\SyliusAkeneoPlugin\Filter\ProductFilterInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;

class ProductModelSearchFilterProvider implements ResourceSearchFilterProviderInterface
{
    public function __construct(private ProductFilterInterface $productFilter)
    {
    }

    public function support(PayloadInterface $payload): bool
    {
        return $payload instanceof ProductModelPayload;
    }

    public function get(PayloadInterface $payload): array
    {
        return $this->productFilter->getProductModelFilters();
    }
}
