<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch;

use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\BatchProductModelTask;

class ProductModelBatchTaskProvider implements BatchTaskProviderInterface
{
    public function __construct(private BatchProductModelTask $task)
    {
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof ProductModelPayload;
    }

    public function getTask(): BatchTaskInterface
    {
        return $this->task;
    }
}
