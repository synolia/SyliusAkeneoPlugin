<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Sylius\Component\Core\Model\ProductVariantInterface;

final class ProductVariantProcessorChain implements ProductVariantProcessorChainInterface
{
    /** @var array<ProductVariantProcessorInterface> */
    private array $productProcessors;

    public function __construct(\Traversable $handlers)
    {
        $this->productProcessors = iterator_to_array($handlers);
    }

    public function chain(ProductVariantInterface $productVariant, array $resource): void
    {
        foreach ($this->productProcessors as $processor) {
            $processor->process($productVariant, $resource);
        }
    }
}
