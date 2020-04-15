<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;

final class DummyTask implements AkeneoTaskInterface
{
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        // This function does nothing on purpose.
        return $payload;
    }
}
