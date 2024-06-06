<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\MessageHandler\Batch;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\AttributeBatchMessage;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Attribute\AttributeResourceProcessor;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;

#[AsMessageHandler]
class AttributeBatchMessageHandler
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private AttributeResourceProcessor $resourceProcessor,
    ) {
    }

    public function __invoke(AttributeBatchMessage $attributeBatchMessage): void
    {
        foreach ($attributeBatchMessage->items as $resource) {
            try {
                $this->resourceProcessor->process($resource);
            } catch (MaxResourceProcessorRetryException) {
                // Skip the failing line
                $this->dispatcher->dispatch(new AttributeBatchMessage([$resource]));

                continue;
            }
        }
    }
}
