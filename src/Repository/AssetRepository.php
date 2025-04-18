<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMapping;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Synolia\SyliusAkeneoPlugin\Entity\Asset;

/**
 * @method Asset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Asset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Asset[]    findAll()
 * @method Asset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class AssetRepository extends EntityRepository
{
    public function __construct(
        #[Autowire('@doctrine.orm.default_entity_manager')]
        EntityManagerInterface $entityManager,
        #[Autowire('@sylius.asset_class_metadata')]
        ClassMetadata $class,
    ) {
        parent::__construct($entityManager, $class);
    }

    public function cleanAssetsForProduct(ProductInterface $product): void
    {
        $query = $this->_em
            ->createNativeQuery(
                'DELETE FROM akeneo_assets_products WHERE owner_id = :product_id',
                new ResultSetMapping(),
            )
            ->setParameter('product_id', $product->getId())
        ;

        $query->execute();
    }
}
