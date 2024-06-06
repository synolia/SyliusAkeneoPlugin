<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch;

use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AssociationType\BatchAssociationTypesTask;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;

class AssociationTypeBatchTaskProvider implements BatchTaskProviderInterface
{
    public function __construct(private BatchAssociationTypesTask $task)
    {
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof AssociationTypePayload;
    }

    public function getTask(): BatchTaskInterface
    {
        return $this->task;
    }
}
