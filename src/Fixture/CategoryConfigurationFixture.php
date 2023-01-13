<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class CategoryConfigurationFixture extends AbstractFixture
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FactoryInterface $categoriesConfigurationFactory,
    ) {
    }

    public function load(array $options): void
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration $categoryConfiguration */
        $categoryConfiguration = $this->categoriesConfigurationFactory->createNew();
        $categoryConfiguration->setRootCategories($options['root_categories_to_import']);
        $categoryConfiguration->setNotImportCategories($options['categories_to_exclude']);

        $this->entityManager->persist($categoryConfiguration);
        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'akeneo_category_configuration';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->arrayNode('root_categories_to_import')
                    ->scalarPrototype()->defaultValue([])->end()
                ->end()
                ->arrayNode('categories_to_exclude')
                    ->scalarPrototype()->defaultValue([])->end()
                ->end()
            ->end()
        ;
    }
}
