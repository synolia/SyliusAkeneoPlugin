<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\MessageHandler\Batch;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\AssociationTypeBatchMessage;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\AssociationType\AssociationTypeResourceProcessor;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;

#[AsMessageHandler]
class AssociationTypeBatchMessageHandler
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private AssociationTypeResourceProcessor $resourceProcessor,
    ) {
    }

    public function __invoke(AssociationTypeBatchMessage $attributeBatchMessage): void
    {
        foreach ($attributeBatchMessage->items as $resource) {
            try {
                $this->resourceProcessor->process($resource);
            } catch (MaxResourceProcessorRetryException) {
                // Skip the failing line
                $this->dispatcher->dispatch(new AssociationTypeBatchMessage([$resource]));

                continue;
            }
        }
    }
}