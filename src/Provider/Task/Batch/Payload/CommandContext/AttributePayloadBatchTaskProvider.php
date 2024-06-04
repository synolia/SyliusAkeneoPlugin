<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch\Payload\CommandContext;

use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

class AttributePayloadBatchTaskProvider implements PayloadBatchTaskProviderInterface
{
    public function createCommandContextBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new AttributePayload($payload->getAkeneoPimClient(), $commandContext);
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof AttributePayload;
    }
}
