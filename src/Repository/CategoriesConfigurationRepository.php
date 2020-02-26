<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\CategoriesConfiguration;

final class CategoriesConfigurationRepository extends EntityRepository
{
    public function getCategoriesConfiguration(): ?CategoriesConfiguration
    {
        $categoriesConfiguration = $this->findOneBy([]);
        if (!$categoriesConfiguration instanceof CategoriesConfiguration) {
            return null;
        }

        return $categoriesConfiguration;
    }
}
