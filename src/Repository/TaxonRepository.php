<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class TaxonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        parent::__construct($registry, $parameterBag->get('sylius.model.taxon.class'));
    }

    public function getMissingCategoriesIds(array $codes): iterable
    {
        return $this->createQueryBuilder('t')
            ->select('t.id')
            ->join('t.parent', 'parent', 'with', 'parent.id = t.parent')
            ->join('t.root', 'root', 'with', 'root.id = t.root')
            ->where('t.code NOT IN (:codes)')
            ->andWhere('parent.code NOT IN (:codes) OR root.code NOT IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
        ;
    }
}
