<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Category;

use Sylius\Component\Core\Model\TaxonInterface;

interface CategoryProcessorInterface
{
    public const TAG_ID = 'sylius.akeneo.category_processor';

    public function process(TaxonInterface $taxon, array $resource): void;

    public function support(TaxonInterface $taxon, array $resource): bool;
}
