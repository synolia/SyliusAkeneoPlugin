<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @method ProductInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductInterface[]    findAll()
 * @method ProductInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<ProductInterface>
 */
final class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        /** @var class-string<ProductInterface> $entityClass */
        $entityClass = $parameterBag->get('sylius.model.product.class');

        parent::__construct($registry, $entityClass);
    }

    public function findProductsUsingCategories(array $ids): iterable
    {
        return $this->createQueryBuilder('p')
            ->where('p.mainTaxon IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }
}
