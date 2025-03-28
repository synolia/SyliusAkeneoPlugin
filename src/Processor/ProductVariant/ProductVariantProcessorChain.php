<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ProductVariantProcessorChain implements ProductVariantProcessorChainInterface
{
    public function __construct(
        #[AutowireIterator(ProductVariantProcessorInterface::class)]
        private iterable $productVariantProcessors,
        private LoggerInterface $akeneoLogger
    ) {
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
