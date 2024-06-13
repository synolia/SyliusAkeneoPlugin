<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\MessageHandler\Batch;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\CategoryBatchMessage;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Category\CategoryResourceProcessor;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;

#[AsMessageHandler]
class CategoryBatchMessageHandler
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private CategoryResourceProcessor $resourceProcessor,
    ) {
    }

    public function __invoke(CategoryBatchMessage $attributeBatchMessage): void
    {
        foreach ($attributeBatchMessage->items as $resource) {
            try {
                $this->resourceProcessor->process($resource);
            } catch (MaxResourceProcessorRetryException) {
                // Skip the failing line
                $this->dispatcher->dispatch(new CategoryBatchMessage([$resource]));

                continue;
            }
        }
    }
}
