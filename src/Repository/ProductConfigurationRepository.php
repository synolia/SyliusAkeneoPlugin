<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Provider\ProductConfigurationProviderInterface;

final class ProductConfigurationRepository extends EntityRepository implements ProductConfigurationProviderInterface
{
    public function getProductConfiguration(): ?ProductConfiguration
    {
        return $this->findOneBy([]);
    }
}
