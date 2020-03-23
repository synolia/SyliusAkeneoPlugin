<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\Product;

final class ProductRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $productManager)
    {
        parent::__construct($productManager, new ClassMetadata(Product::class));
    }

    public function findProductsUsingCategories(array $ids): iterable
    {
        return $this->createQueryBuilder('p')
            ->where('p.mainTaxon IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
