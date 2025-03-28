<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;

final class ProductGroupRepository extends EntityRepository
{
    public function isProductInProductGroup(
        #[Autowire('@doctrine.orm.default_entity_manager')]
        ProductInterface $product,
        #[Autowire('@akeneo.product_group')]
        ProductGroupInterface $productGroup,
    ): int {
        $query = $this->createQueryBuilder('p');

        return (int) $query
            ->select('count(p)')
            ->join('p.products', 'v')
            ->where('p = :productGroup')
            ->andWhere($query->expr()->eq('v.code', ':code'))
            ->setParameter('productGroup', $productGroup)
            ->setParameter('code', $product->getCode())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
