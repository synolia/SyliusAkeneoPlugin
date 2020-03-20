<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Table;

/**
 * @MappedSuperclass
 * @Table(name="sylius_product_attribute")
 */
class ProductAttribute extends \Sylius\Component\Product\Model\ProductAttribute
{
}
