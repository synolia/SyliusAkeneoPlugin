<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch;

use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Category\BatchCategoriesTask;

class CategoryBatchTaskProvider implements BatchTaskProviderInterface
{
    public function __construct(private BatchCategoriesTask $task)
    {
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof CategoryPayload;
    }

    public function getTask(): BatchTaskInterface
    {
        return $this->task;
    }
}
