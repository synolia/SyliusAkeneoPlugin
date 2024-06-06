<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;

#[AutoconfigureTag()]
interface BatchTaskProviderInterface
{
    public function support(PipelinePayloadInterface $pipelinePayload): bool;

    public function getTask(): BatchTaskInterface;
}
