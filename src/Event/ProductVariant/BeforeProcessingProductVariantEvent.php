<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\ProductVariant;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

class BeforeProcessingProductVariantEvent extends AbstractResourceEvent
{
    /** @var \Sylius\Component\Core\Model\ProductInterface */
    private $product;

    public function __construct(array $resource, ProductInterface $taxon)
    {
        parent::__construct($resource);

        $this->product = $taxon;
    }

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }
}
