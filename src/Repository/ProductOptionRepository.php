<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

final class ProductOptionRepository extends EntityRepository
{
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
