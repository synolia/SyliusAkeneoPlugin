<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\MessageHandler\Batch;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\ProductVariantBatchMessage;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\ProductVariant\ProductVariantResourceProcessor;

#[AsMessageHandler]
class ProductVariantBatchMessageHandler
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private ProductVariantResourceProcessor $resourceProcessor,
    ) {
    }

    public function __invoke(ProductVariantBatchMessage $productVariantBatchMessage): void
    {
        foreach ($productVariantBatchMessage->items as $resource) {
            try {
                $this->resourceProcessor->process($resource);
            } catch (MaxResourceProcessorRetryException) {
                // Skip the failing line
                $this->dispatcher->dispatch(new ProductVariantBatchMessage([$resource]));

                continue;
            }
        }
    }
}
