<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Sylius\Component\Product\Model\ProductOption;

final class ProductOptionRepository extends ServiceEntityRepository
{
    /**
     * ProductOptionRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductOption::class);
    }

    public function getRemovedOptionIds(array $codes): array
    {
        $removedOptionResults = $this->createQueryBuilder('o')
            ->select('o.id')
            ->where('o.code NOT IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
        ;

        if (0 === \count($removedOptionResults)) {
            return [];
        }

        return \array_map(function (array $data) {
            return $data['id'];
        }, $removedOptionResults);
    }
}
