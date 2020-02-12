<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\AkeneoCategoriesConfiguration;

final class AkeneoCategoriesConfigurationRepository extends EntityRepository
{
    public function getCategoriesConfiguration(): ?AkeneoCategoriesConfiguration
    {
        $categoriesConfiguration = $this->findAll();
        if (empty($categoriesConfiguration)) {
            return null;
        }

        return $categoriesConfiguration[0];
    }
}
