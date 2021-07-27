<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @method ProductTaxonInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductTaxonInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductTaxonInterface[]    findAll()
 * @method ProductTaxonInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<ProductTaxonInterface>
 */
final class ProductTaxonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        /** @var class-string<ProductTaxonInterface> $entityClass */
        $entityClass = $parameterBag->get('sylius.model.product_taxon.class');

        parent::__construct($registry, $entityClass);
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
