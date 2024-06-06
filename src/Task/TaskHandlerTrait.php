<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Handler\Task\TaskHandlerProviderInterface;

trait TaskHandlerTrait
{
    public function __construct(
        private TaskHandlerProviderInterface $taskHandlerProvider,
    ) {
    }

    protected function continue(PipelinePayloadInterface $pipelinePayload): void
    {
        $this->taskHandlerProvider->provide($pipelinePayload)->continue($pipelinePayload);
    }

    protected function handle(
        PipelinePayloadInterface $pipelinePayload,
        ResourceCursorInterface|PageInterface $handleType,
    ): void {
        $this->taskHandlerProvider->provide($pipelinePayload)->handle($pipelinePayload, $handleType);
    }
}
