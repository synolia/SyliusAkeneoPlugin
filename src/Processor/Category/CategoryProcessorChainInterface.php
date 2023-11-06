<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Category;

use Sylius\Component\Core\Model\TaxonInterface;

interface CategoryProcessorChainInterface
{
    public function chain(TaxonInterface $taxon, array $resource): void;
}
