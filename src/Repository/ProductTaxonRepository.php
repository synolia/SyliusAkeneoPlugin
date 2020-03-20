<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxon;
use Sylius\Component\Core\Model\ProductTaxonInterface;

final class ProductTaxonRepository extends ServiceEntityRepository
{
    /**
     * ProductTaxonRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductTaxon::class);
    }

    public function getProductTaxonIds(ProductInterface $product): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function removeProductTaxonById(int $productTaxonId): void
    {
        $this->createQueryBuilder('t')
            ->delete(ProductTaxonInterface::class, 't')
            ->where('t.id = :productTaxonid')
            ->setParameter('productTaxonid', $productTaxonId)
            ->getQuery()
            ->execute()
        ;
    }
}
