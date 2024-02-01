<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\ORM\Query\ResultSetMapping;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Entity\Asset;

/**
 * @method Asset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Asset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Asset[]    findAll()
 * @method Asset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class AssetRepository extends EntityRepository
{
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
