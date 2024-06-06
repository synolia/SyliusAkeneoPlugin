<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch;

use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Product\BatchProductsTask;

class ProductVariantBatchTaskProvider implements BatchTaskProviderInterface
{
    public function __construct(private BatchProductsTask $task)
    {
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof ProductPayload;
    }

    public function getTask(): BatchTaskInterface
    {
        return $this->task;
    }
}
