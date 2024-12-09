<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Filter\Resource;

use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

class AttributeSearchFilterProvider implements ResourceSearchFilterProviderInterface
{
    public function support(PayloadInterface $payload): bool
    {
        return $payload instanceof AttributePayload;
    }

    public function get(PayloadInterface $payload): array
    {
        return ['with_table_select_options' => true];
    }
}
