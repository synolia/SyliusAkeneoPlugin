<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Category;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class CategoryProcessorChain implements CategoryProcessorChainInterface
{
    public function __construct(
        /** @var iterable<CategoryProcessorInterface> $categoryProcessors */
        #[AutowireIterator(CategoryProcessorInterface::TAG_ID)]
        private iterable $categoryProcessors,
        private LoggerInterface $akeneoLogger,
    ) {
    }

    public function chain(TaxonInterface $taxon, array $resource): void
    {
        foreach ($this->categoryProcessors as $processor) {
            if ($processor->support($taxon, $resource)) {
                $this->akeneoLogger->debug(sprintf('Begin %s', $processor::class), [
                    'taxon_code' => $taxon->getCode(),
                ]);

                $processor->process($taxon, $resource);

                $this->akeneoLogger->debug(sprintf('End %s', $processor::class), [
                    'taxon_code' => $taxon->getCode(),
                ]);
            }
        }
    }
}
