<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        parent::__construct($registry, $parameterBag->get('sylius.model.channel.class'));
    }

    public function findByCurrencyCode(string $currencyCode): iterable
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.baseCurrency', 'bc', 'WITH', 'bc.id = c.baseCurrency')
            ->where('bc.code = :currencyCode')
            ->setParameter('currencyCode', $currencyCode)
            ->getQuery()
            ->getResult()
        ;
    }
}
