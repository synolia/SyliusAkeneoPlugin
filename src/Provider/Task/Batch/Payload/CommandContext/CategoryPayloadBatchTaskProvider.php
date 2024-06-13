<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch\Payload\CommandContext;

use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

class CategoryPayloadBatchTaskProvider implements PayloadBatchTaskProviderInterface
{
    public function createCommandContextBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new CategoryPayload($payload->getAkeneoPimClient(), $commandContext);
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof CategoryPayload;
    }
}
