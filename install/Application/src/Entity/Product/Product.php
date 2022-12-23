<?php

declare(strict_types=1);

namespace App\Entity\Product;

use App\Entity\Product\ProductTranslation;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Sylius\Component\Product\Model\ProductTranslationInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductAssetTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product")
 */
class Product extends BaseProduct
{
    use ProductAssetTrait {
        __construct as private initializeAssetsCollection;
    }

    public function __construct()
    {
        parent::__construct();
        $this->initializeAssetsCollection();
    }

    protected function createTranslation(): ProductTranslationInterface
    {
        return new ProductTranslation();
    }
}
