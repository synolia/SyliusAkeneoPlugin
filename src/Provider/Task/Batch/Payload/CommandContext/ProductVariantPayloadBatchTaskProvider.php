<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Task\Batch\Payload\CommandContext;

use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;

class ProductVariantPayloadBatchTaskProvider implements PayloadBatchTaskProviderInterface
{
    public function createCommandContextBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new ProductPayload($payload->getAkeneoPimClient(), $commandContext);
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload instanceof ProductPayload;
    }
}
