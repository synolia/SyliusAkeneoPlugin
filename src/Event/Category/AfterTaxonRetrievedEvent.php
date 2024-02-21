<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\Category;

use Sylius\Component\Core\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

final class AfterTaxonRetrievedEvent extends AbstractResourceEvent
{
    public function __construct(array $resource, private TaxonInterface $taxon)
    {
        parent::__construct($resource);
    }

    public function getTaxon(): TaxonInterface
    {
        return $this->taxon;
    }
}
