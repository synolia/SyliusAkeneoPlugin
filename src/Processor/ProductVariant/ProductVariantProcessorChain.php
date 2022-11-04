<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Traversable;

final class ProductVariantProcessorChain implements ProductVariantProcessorChainInterface
{
    /** @var array<ProductVariantProcessorInterface> */
    private array $productVariantProcessors;

    private LoggerInterface $logger;

    public function __construct(Traversable $handlers, LoggerInterface $logger)
    {
        $this->productVariantProcessors = iterator_to_array($handlers);
        $this->logger = $logger;
    }

    public function chain(ProductVariantInterface $productVariant, array $resource): void
    {
        foreach ($this->productVariantProcessors as $processor) {
            if ($processor->support($productVariant, $resource)) {
                $this->logger->debug(sprintf('Begin %s', \get_class($processor)), [
                    'product_variant_code' => $productVariant->getCode(),
                ]);

                $processor->process($productVariant, $resource);

                $this->logger->debug(sprintf('End %s', \get_class($processor)), [
                    'product_variant_code' => $productVariant->getCode(),
                ]);
            }
        }
    }
}
