<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ProductAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        parent::__construct($registry, $parameterBag->get('sylius.model.product_attribute.class'));
    }

    /**
     * @param array<string> $codes
     *
     * @return array<int, int>
     */
    public function getMissingAttributesIds(array $codes): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id')
            ->where('a.code NOT IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array<string> $codes
     *
     * @return array<int, int>
     */
    public function findByCodes(array $codes): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getAllAttributeCodes(): array
    {
        $attributeCodesResult = $this->createQueryBuilder('a')
            ->select('a.code')
            ->getQuery()
            ->getResult()
        ;

        if (0 === \count($attributeCodesResult)) {
            return [];
        }

        return \array_map(fn (array $data) => $data['code'], $attributeCodesResult);
    }
}
