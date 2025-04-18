<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Category;

use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: self::TAG_ID)]
interface CategoryProcessorInterface
{
    public const TAG_ID = 'sylius.akeneo.category_processor';

    public function process(TaxonInterface $taxon, array $resource): void;

    public function support(TaxonInterface $taxon, array $resource): bool;
}
