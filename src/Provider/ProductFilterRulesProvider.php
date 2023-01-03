<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;

final class ProductFilterRulesProvider implements ProductFilterRulesProviderInterface
{
    private ProductFiltersRules $productFiltersRules;

    public function __construct(private ProductFiltersRulesRepository $productFiltersRulesRepository)
    {
    }

    /**
     * @throws NoProductFiltersConfigurationException
     */
    public function getProductFiltersRules(): ProductFiltersRules
    {
        if (isset($this->productFiltersRules)) {
            return $this->productFiltersRules;
        }

        $productFiltersRules = $this->productFiltersRulesRepository->findOneBy([], ['id' => 'DESC']);

        if (!$productFiltersRules instanceof ProductFiltersRules) {
            throw new NoProductFiltersConfigurationException('Product filters must be configured before importing product attributes.');
        }

        $this->productFiltersRules = $productFiltersRules;

        return $this->productFiltersRules;
    }
}
