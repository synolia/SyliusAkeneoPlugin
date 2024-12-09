<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Category;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Traversable;

final class CategoryProcessorChain implements CategoryProcessorChainInterface
{
    /** @var array<CategoryProcessorInterface> */
    private array $categoryProcessors;

    public function __construct(Traversable $handlers, private LoggerInterface $akeneoLogger)
    {
        $this->categoryProcessors = iterator_to_array($handlers);
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
