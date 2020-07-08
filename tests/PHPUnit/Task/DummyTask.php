<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task;

use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class DummyTask implements AkeneoTaskInterface
{
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        // This function does nothing on purpose.
        return $payload;
    }
}
