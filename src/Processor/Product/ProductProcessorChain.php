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

    public function __construct(Traversable $handlers, private LoggerInterface $logger)
    {
        $this->productProcessors = iterator_to_array($handlers);
    }

    public function chain(ProductInterface $product, array $resource): void
    {
        foreach ($this->productProcessors as $processor) {
            if ($processor->support($product, $resource)) {
                $this->logger->debug(sprintf('Begin %s', $processor::class), [
                    'product_code' => $product->getCode(),
                ]);

                $processor->process($product, $resource);

                $this->logger->debug(sprintf('End %s', $processor::class), [
                    'product_code' => $product->getCode(),
                ]);
            }
        }
    }
}
