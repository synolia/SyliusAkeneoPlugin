<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Sylius\Component\Core\Model\ProductInterface;

final class ProductProcessorChain implements ProductProcessorChainInterface
{
    /** @var array<ProductProcessorInterface> */
    private array $productProcessors;

    public function __construct(\Traversable $handlers)
    {
        $this->productProcessors = iterator_to_array($handlers);
    }

    public function chain(ProductInterface $product, array $resource): void
    {
        foreach ($this->productProcessors as $processor) {
            $processor->process($product, $resource);
        }
    }
}
