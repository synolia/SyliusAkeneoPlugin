<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;

final class CategoryConfigurationRepository extends EntityRepository
{
    public function __construct(
        #[Autowire('@doctrine.orm.default_entity_manager')]
        EntityManagerInterface $entityManager,
        #[Autowire('@akeneo.category_configuration_class_metadata')]
        ClassMetadata $class
    ) {
        parent::__construct($entityManager, $class);
    }

    public function getCategoriesConfiguration(): ?CategoryConfiguration
    {
        $categoriesConfiguration = $this->findOneBy([], ['id' => 'DESC']);

        if (!$categoriesConfiguration instanceof CategoryConfiguration) {
            return null;
        }

        return $categoriesConfiguration;
    }
}
