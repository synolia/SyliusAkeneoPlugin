<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\Channel;

final class ChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Channel::class);
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
