<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;

final class ProductFiltersRulesRepository extends EntityRepository
{
    public function getProductFiltersRules(): ?ProductFiltersRules
    {
        /** @var ProductFiltersRules[] $productfiltersRules */
        $productfiltersRules = $this->findAll();
        if (empty($productfiltersRules)) {
            return null;
        }

        return $productfiltersRules[0];
    }
}
