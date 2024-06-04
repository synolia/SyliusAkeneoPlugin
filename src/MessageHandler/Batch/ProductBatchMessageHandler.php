<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\MessageHandler\Batch;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\ProductBatchMessage;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Product\ProductModelResourceProcessor;

#[AsMessageHandler]
class ProductBatchMessageHandler
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private ProductModelResourceProcessor $resourceProcessor,
    ) {
    }

    public function __invoke(ProductBatchMessage $productModelBatchMessage): void
    {
        foreach ($productModelBatchMessage->items as $resource) {
            try {
                $this->resourceProcessor->process($resource);
            } catch (MaxResourceProcessorRetryException) {
                // Skip the failing line
                $this->dispatcher->dispatch(new ProductBatchMessage([$resource]));

                continue;
            }
        }
    }
}
