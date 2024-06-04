<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch;

use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\BatchAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;

class AttributeBatchTaskProvider implements BatchTaskProviderInterface
{
    public function __construct(private BatchAttributesTask $task)
    {
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof AttributePayload;
    }

    public function getTask(): BatchTaskInterface
    {
        return $this->task;
    }
}
