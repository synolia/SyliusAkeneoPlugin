<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class AttributeAkeneoSyliusMappingFixture extends AbstractFixture
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FactoryInterface $attributeAkeneoSyliusMappingFactory,
    ) {
    }

    public function load(array $options): void
    {
        foreach ($options['mappings'] as $mapping) {
            /** @var \Synolia\SyliusAkeneoPlugin\Entity\AttributeAkeneoSyliusMapping $attributeAkeneoSyliusMapping */
            $attributeAkeneoSyliusMapping = $this->attributeAkeneoSyliusMappingFactory->createNew();
            $attributeAkeneoSyliusMapping->setAkeneoAttribute($mapping['akeneo_attribute']);
            $attributeAkeneoSyliusMapping->setSyliusAttribute($mapping['sylius_attribute']);
            $this->entityManager->persist($attributeAkeneoSyliusMapping);
        }

        $this->entityManager->flush();
    }

    public function getName(): string
    {
        return 'akeneo_attribute_akeneo_sylius_mapping';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->arrayNode('mappings')
                    ->arrayPrototype()
                    ->children()
                        ->scalarNode('akeneo_attribute')->end()
                        ->scalarNode('sylius_attribute')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
