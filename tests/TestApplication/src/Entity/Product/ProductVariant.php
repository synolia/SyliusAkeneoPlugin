<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\TestApplication\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductVariant as BaseProductVariant;
use Synolia\SyliusAkeneoPlugin\Entity\ProductVariantAssetTrait;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_product_variant')]
class ProductVariant extends BaseProductVariant
{
    use ProductVariantAssetTrait {
        ProductVariantAssetTrait::__construct as private initializeAssetsCollection;
    }

    public function __construct()
    {
        parent::__construct();

        $this->initializeAssetsCollection();
    }
}
