<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\Taxon;

final class TaxonRepository extends ServiceEntityRepository
{
    /**
     * TaxonRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Taxon::class);
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
