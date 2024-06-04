<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Handler\Task\TaskHandlerProviderInterface;

final class SetupTask implements AkeneoTaskInterface
{
    public function __construct(
        private TaskHandlerProviderInterface $taskHandlerProvider,
    ) {
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        return $this->taskHandlerProvider->provide($payload)->setUp($payload);
    }
}
