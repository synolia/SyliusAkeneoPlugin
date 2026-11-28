<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\TestApplication\Entity\Taxonomy;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Taxon as BaseTaxon;
use Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Model\TaxonAttributeSubjectInterface;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributesTrait;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_taxon')]
class Taxon extends BaseTaxon implements TaxonAttributeSubjectInterface
{
    use TaxonAttributesTrait {
        __construct as private initializeTaxonAttributes;
    }

    public function __construct()
    {
        parent::__construct();

        $this->initializeTaxonAttributes();
    }
}
