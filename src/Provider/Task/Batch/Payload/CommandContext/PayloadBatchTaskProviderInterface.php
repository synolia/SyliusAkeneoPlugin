<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch\Payload\CommandContext;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

#[AutoconfigureTag()]
interface PayloadBatchTaskProviderInterface
{
    public function support(PipelinePayloadInterface $pipelinePayload): bool;

    public function createCommandContextBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface;
}
