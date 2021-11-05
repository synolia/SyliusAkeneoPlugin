<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

interface BatchTaskInterface
{
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface;
}
