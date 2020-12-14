<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;

class CategoryConfigurationFixture extends AbstractFixture
{
    private ObjectManager $objectManager;

    private FactoryInterface $categoriesConfigurationFactory;

    public function __construct(
        ObjectManager $objectManager,
        FactoryInterface $categoriesConfigurationFactory
    ) {
        $this->objectManager = $objectManager;
        $this->categoriesConfigurationFactory = $categoriesConfigurationFactory;
    }

    public function load(array $options): void
    {
        /** @var CategoryConfiguration $categoryConfiguration */
        $categoryConfiguration = $this->categoriesConfigurationFactory->createNew();
        $categoryConfiguration->setRootCategories($options['root_categories_to_import']);
        $categoryConfiguration->setNotImportCategories($options['categories_to_exclude']);

        $this->objectManager->persist($categoryConfiguration);
        $this->objectManager->flush();
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
