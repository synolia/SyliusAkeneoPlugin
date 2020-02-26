<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

final class ProductRepository extends EntityRepository
{
    public function findProductsUsingCategories(array $ids): iterable
    {
        return $this->createQueryBuilder('p')
            ->where('p.mainTaxon IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
