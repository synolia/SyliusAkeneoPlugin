<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\TestApplication\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Synolia\SyliusAkeneoPlugin\Entity\ProductAssetTrait;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_product')]
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
}
