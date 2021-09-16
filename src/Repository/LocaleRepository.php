<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @extends ServiceEntityRepository<LocaleInterface>
 */
final class LocaleRepository extends ServiceEntityRepository implements LocaleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        /** @var class-string<LocaleInterface> $entityClass */
        $entityClass = $parameterBag->get('sylius.model.locale.class');

        parent::__construct($registry, $entityClass);
    }

    public function getLocaleCodes(): iterable
    {
        $values = $this->createQueryBuilder('locale')
            ->select('locale.code')
            ->getQuery()
            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        ;

        return \array_map(function ($value) {
            return $value['code'];
        }, $values);
    }
}
