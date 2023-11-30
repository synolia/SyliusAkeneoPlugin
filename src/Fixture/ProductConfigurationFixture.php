<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class ProductConfigurationFixture extends AbstractFixture
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FactoryInterface $productConfigurationFactory,
        private FactoryInterface $productImageAttributeConfigurationFactory,
        private FactoryInterface $productImageMappingConfigurationFactory,
    ) {
    }

    public function load(array $options): void
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration $productConfiguration */
        $productConfiguration = $this->productConfigurationFactory->createNew();
        $productConfiguration->setAkeneoEnabledChannelsAttribute($options['akeneo_sylius_enabled_channels_attribute']);
        $productConfiguration->setAkeneoPriceAttribute($options['akeneo_price_attribute']);
        $productConfiguration->setRegenerateUrlRewrites($options['regenerate_url']);
        $productConfiguration->setImportMediaFiles($options['import_media_files']);
        $this->entityManager->persist($productConfiguration);

        foreach ($options['images_attributes'] as $imagesAttribute) {
            /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAkeneoImageAttribute $productImageAttribute */
            $productImageAttribute = $this->productImageAttributeConfigurationFactory->createNew();
            $productImageAttribute->setAkeneoAttributes($imagesAttribute);
            $productConfiguration->addAkeneoImageAttribute($productImageAttribute);
            $this->entityManager->persist($productImageAttribute);
        }

        foreach ($options['images_type_mapping'] as $imagesTypeMapping) {
            /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping $productImageMapping */
            $productImageMapping = $this->productImageMappingConfigurationFactory->createNew();
            $productImageMapping->setAkeneoAttribute($imagesTypeMapping['akeneo_attribute']);
            $productImageMapping->setSyliusAttribute($imagesTypeMapping['type']);
            $productConfiguration->addProductImagesMapping($productImageMapping);
        }

        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'akeneo_product_configuration';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->scalarNode('akeneo_sylius_enabled_channels_attribute')->defaultNull()->end()
                ->scalarNode('akeneo_price_attribute')->defaultNull()->end()
                ->booleanNode('regenerate_url')->defaultFalse()->end()
                ->booleanNode('import_media_files')->defaultFalse()->end()
                ->arrayNode('images_attributes')
                    ->scalarPrototype()->defaultValue([])->end()
                ->end()
                ->arrayNode('images_type_mapping')
                    ->arrayPrototype()
                    ->children()
                        ->scalarNode('akeneo_attribute')->end()
                        ->scalarNode('type')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
