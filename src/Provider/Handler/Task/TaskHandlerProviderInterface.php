<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Handler\Task;

use Synolia\SyliusAkeneoPlugin\Handler\Task\TaskHandlerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

interface TaskHandlerProviderInterface
{
    public function provide(PipelinePayloadInterface $pipelinePayload): TaskHandlerInterface;
}
