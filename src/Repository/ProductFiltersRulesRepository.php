<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;

final class ProductFiltersRulesRepository extends EntityRepository
{
    public function __construct(
        #[Autowire('@doctrine.orm.default_entity_manager')]
        EntityManagerInterface $entityManager,
        #[Autowire('@sylius.product_filters_rules_class_metadata')]
        ClassMetadata $class,
    ) {
        parent::__construct($entityManager, $class);
    }

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
