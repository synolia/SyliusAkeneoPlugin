<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\CategorieConfiguration;

final class CategorieConfigurationRepository extends EntityRepository
{
    public function getCategoriesConfiguration(): ?CategorieConfiguration
    {
        $categoriesConfiguration = $this->findOneBy([]);
        if (!$categoriesConfiguration instanceof CategorieConfiguration) {
            return null;
        }

        return $categoriesConfiguration;
    }
}
