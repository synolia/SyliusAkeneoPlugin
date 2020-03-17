<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;

final class ProductTaxonRepository extends EntityRepository
{
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
