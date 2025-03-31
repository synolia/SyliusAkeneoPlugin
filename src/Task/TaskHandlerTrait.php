<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\Payload\PostHandlePayloadEvent;
use Synolia\SyliusAkeneoPlugin\Event\Payload\PreHandlePayloadEvent;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Handler\Task\TaskHandlerProviderInterface;

trait TaskHandlerTrait
{
    public function __construct(
        private TaskHandlerProviderInterface $taskHandlerProvider,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    protected function continue(PipelinePayloadInterface $pipelinePayload): void
    {
        $this->taskHandlerProvider->provide($pipelinePayload)->continue($pipelinePayload);
    }

    protected function handle(
        PipelinePayloadInterface $pipelinePayload,
        iterable|PageInterface $handleType,
    ): void {
        $this->dispatcher->dispatch(new PreHandlePayloadEvent($pipelinePayload));
        $this->taskHandlerProvider->provide($pipelinePayload)->handle($pipelinePayload, $handleType);
        $this->dispatcher->dispatch(new PostHandlePayloadEvent($pipelinePayload));
    }
}
