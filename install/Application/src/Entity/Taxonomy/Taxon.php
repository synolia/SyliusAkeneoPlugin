<?php

declare(strict_types=1);

namespace App\Entity\Taxonomy;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Taxon as BaseTaxon;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Model\TaxonAttributeSubjectInterface;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributesTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_taxon")
 */
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

        $this->createTranslation();
        $this->initializeTaxonAttributes();
    }

    protected function createTranslation(): TaxonTranslationInterface
    {
        return new TaxonTranslation();
    }
}
