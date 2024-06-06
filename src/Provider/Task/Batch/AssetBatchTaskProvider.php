<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch;

use Synolia\SyliusAkeneoPlugin\Payload\Asset\AssetPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\Asset\BatchAssetTask;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;

class AssetBatchTaskProvider implements BatchTaskProviderInterface
{
    public function __construct(private BatchAssetTask $task)
    {
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof AssetPayload;
    }

    public function getTask(): BatchTaskInterface
    {
        return $this->task;
    }
}
