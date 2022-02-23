<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Traversable;

final class ProductProcessorChain implements ProductProcessorChainInterface
{
    /** @var array<ProductProcessorInterface> */
    private array $productProcessors;

    private LoggerInterface $logger;

    public function __construct(Traversable $handlers, LoggerInterface $logger)
    {
        $this->productProcessors = iterator_to_array($handlers);
        $this->logger = $logger;
    }

    public function chain(ProductInterface $product, array $resource): void
    {
        foreach ($this->productProcessors as $processor) {
            if ($processor->support($product, $resource)) {
                $this->logger->debug(sprintf('Begin %s', \get_class($processor)), [
                    'product_code' => $product->getCode(),
                ]);

                $processor->process($product, $resource);

                $this->logger->debug(sprintf('End %s', \get_class($processor)), [
                    'product_code' => $product->getCode(),
                ]);
            }
        }
    }
}
