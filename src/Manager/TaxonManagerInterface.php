<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Sylius\Component\Core\Model\ProductInterface;

interface TaxonManagerInterface
{
    public function updateTaxon(array $resource, ProductInterface $product): array;

    public function getProductTaxonIds(ProductInterface $product): array;

    public function setMainTaxon(array $resource, ProductInterface $product): void;

    public function removeUnusedProductTaxons(array $productTaxonIds, array $productTaxons): void;
}
