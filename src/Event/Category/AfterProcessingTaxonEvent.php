<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\Category;

use Sylius\Component\Core\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

final class AfterProcessingTaxonEvent extends AbstractResourceEvent
{
    private TaxonInterface $taxon;

    public function __construct(array $resource, TaxonInterface $taxon)
    {
        parent::__construct($resource);

        $this->taxon = $taxon;
    }

    public function getTaxon(): TaxonInterface
    {
        return $this->taxon;
    }
}
