<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ProductProcessorChain implements ProductProcessorChainInterface
{
    public function __construct(
        /** @var iterable<ProductProcessorInterface> $productProcessors */
        #[AutowireIterator(ProductProcessorInterface::TAG_ID)]
        private iterable $productProcessors,
        private LoggerInterface $akeneoLogger,
    ) {
    }

    public function chain(ProductInterface $product, array $resource): void
    {
        foreach ($this->productProcessors as $processor) {
            if ($processor->support($product, $resource)) {
                $this->akeneoLogger->debug(sprintf('Begin %s', $processor::class), [
                    'product_code' => $product->getCode(),
                ]);

                $processor->process($product, $resource);

                $this->akeneoLogger->debug(sprintf('End %s', $processor::class), [
                    'product_code' => $product->getCode(),
                ]);
            }
        }
    }
}
