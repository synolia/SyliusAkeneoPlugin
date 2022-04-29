<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @method TaxonInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaxonInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaxonInterface[]    findAll()
 * @method TaxonInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<TaxonInterface>
 */
final class TaxonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        /** @var class-string<TaxonInterface> $entityClass */
        $entityClass = $parameterBag->get('sylius.model.taxon.class');

        parent::__construct($registry, $entityClass);
    }

    public function getMissingCategoriesIds(array $codes): iterable
    {
        return $this->createQueryBuilder('t')
            ->select('t.id')
            ->join('t.parent', 'parent', Join::WITH, 'parent.id = t.parent')
            ->join('t.root', 'root', Join::WITH, 'root.id = t.root')
            ->where('t.code NOT IN (:codes)')
            ->andWhere('parent.code NOT IN (:codes) OR root.code NOT IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
        ;
    }
}
