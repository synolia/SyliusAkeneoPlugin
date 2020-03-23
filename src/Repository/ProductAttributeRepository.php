<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Product\Model\ProductAttribute;

final class ProductAttributeRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $productAttributeManager)
    {
        parent::__construct($productAttributeManager, new ClassMetadata(ProductAttribute::class));
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

        return \array_map(function (array $data) {
            return $data['code'];
        }, $attributeCodesResult);
    }
}
