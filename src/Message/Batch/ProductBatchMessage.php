<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Message\Batch;

class ProductBatchMessage implements BatchMessageInterface
{
    public function __construct(public array $items)
    {
    }
}
