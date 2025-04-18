<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @method ChannelInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChannelInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChannelInterface[]    findAll()
 * @method ChannelInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<ChannelInterface>
 */
final class ChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        /** @var class-string<ChannelInterface> $entityClass */
        $entityClass = $parameterBag->get('sylius.model.channel.class');

        parent::__construct($registry, $entityClass);
    }

    public function findByCurrencyCode(string $currencyCode): iterable
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.baseCurrency', 'bc')
            ->where('bc.code = :currencyCode')
            ->setParameter('currencyCode', $currencyCode)
            ->getQuery()
            ->getResult()
        ;
    }
}
