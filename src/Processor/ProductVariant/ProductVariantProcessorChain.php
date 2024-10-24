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

    public function __construct(Traversable $handlers, private LoggerInterface $akeneoLogger)
    {
        $this->productVariantProcessors = iterator_to_array($handlers);
    }

    public function chain(ProductVariantInterface $productVariant, array $resource): void
    {
        foreach ($this->productVariantProcessors as $processor) {
            if ($processor->support($productVariant, $resource)) {
                $this->akeneoLogger->debug(sprintf('Begin %s', $processor::class), [
                    'product_variant_code' => $productVariant->getCode(),
                ]);

                $processor->process($productVariant, $resource);

                $this->akeneoLogger->debug(sprintf('End %s', $processor::class), [
                    'product_variant_code' => $productVariant->getCode(),
                ]);
            }
        }
    }
}
