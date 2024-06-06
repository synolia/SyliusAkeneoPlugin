<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch\Payload\CommandContext;

use Synolia\SyliusAkeneoPlugin\Payload\Asset\AssetPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

class AssetPayloadBatchTaskProvider implements PayloadBatchTaskProviderInterface
{
    public function createCommandContextBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new AssetPayload($payload->getAkeneoPimClient(), $commandContext);
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof AssetPayload;
    }
}
