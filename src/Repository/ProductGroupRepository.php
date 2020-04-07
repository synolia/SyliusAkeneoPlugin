<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;

final class ProductGroupRepository extends EntityRepository
{
    public function getProductGroupByProductCode(string $productCode): ?ProductGroup
    {
        $query = $this->createQueryBuilder('p');
        $result = $query->join('p.products', 'v')
            ->where($query->expr()->eq('v.code', ':code'))
            ->setParameter('code', $productCode)
            ->getQuery()
            ->getSingleResult()
        ;

        if (!$result instanceof ProductGroup) {
            return null;
        }

        return $result;
    }
}
