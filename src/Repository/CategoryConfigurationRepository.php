<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;

final class CategoryConfigurationRepository extends EntityRepository
{
    public function getCategoriesConfiguration(): ?CategoryConfiguration
    {
        $categoriesConfiguration = $this->findOneBy([], ['id' => 'DESC']);

        if (!$categoriesConfiguration instanceof CategoryConfiguration) {
            return null;
        }

        return $categoriesConfiguration;
    }
}
