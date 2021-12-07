<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;

final class ProductFilterRulesProvider implements ProductFilterRulesProviderInterface
{
    private ProductFiltersRules $productFiltersRules;

    private ProductFiltersRulesRepository $productFiltersRulesRepository;

    public function __construct(ProductFiltersRulesRepository $productFiltersRulesRepository)
    {
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
    }

    public function getProductFiltersRules(): ProductFiltersRules
    {
        if (isset($this->productFiltersRules)) {
            return $this->productFiltersRules;
        }

        $this->productFiltersRules = $this->productFiltersRulesRepository->findOneBy([]);

        if (!$this->productFiltersRules instanceof ProductFiltersRules) {
            throw new NoProductFiltersConfigurationException('Product filters must be configured before importing product attributes.');
        }

        return $this->productFiltersRules;
    }
}
