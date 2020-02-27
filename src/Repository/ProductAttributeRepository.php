<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

final class ProductAttributeRepository extends EntityRepository
{
    public function getMissingAttributesIds(array $codes): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id')
            ->where('a.code NOT IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByCodes(array $codes): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
        ;
    }
}
