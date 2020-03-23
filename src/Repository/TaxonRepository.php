<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\Taxon;

final class TaxonRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $productTaxonManager)
    {
        parent::__construct($productTaxonManager, new ClassMetadata(Taxon::class));
    }

    public function getMissingCategoriesIds(array $codes): iterable
    {
        return $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.code NOT IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
        ;
    }
}
