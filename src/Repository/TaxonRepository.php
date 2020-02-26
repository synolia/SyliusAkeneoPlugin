<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

final class TaxonRepository extends EntityRepository
{
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
