<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ProductConfigurationRepository extends EntityRepository
{
    public function __construct(
        #[Autowire('@doctrine.orm.default_entity_manager')]
        EntityManagerInterface $entityManager,
        #[Autowire('@sylius.product_configuration_class_metadata')]
        ClassMetadata $class,
    ) {
        parent::__construct($entityManager, $class);
    }
}
