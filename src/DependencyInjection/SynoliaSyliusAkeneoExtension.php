<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Synolia\SyliusAkeneoPlugin\Controller\CategoriesController;
use Synolia\SyliusAkeneoPlugin\Menu\AdminCategoryMenuListener;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\DatabaseCategoryConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\DotEnvApiConnectionProvider;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\ExcludedAttributesConfiguration;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\ExcludedAttributesConfigurationInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\LocaleMappingConfiguration;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\LocaleMappingConfigurationInterface;

final class SynoliaSyliusAkeneoExtension extends Extension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        $this->processApiConfiguration($container);
        $this->processCategoryConfiguration($container, $config);
        $this->processLocaleMapping($container, $config);
        $this->processExcludedAttributes($container, $config);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('monolog', ['channels' => ['akeneo']]);

        $this->prependDoctrineMigrations($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Synolia\SyliusAkeneoPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@SynoliaSyliusAkeneoPlugin/migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return ['Sylius\Bundle\CoreBundle\Migrations'];
    }

    private function processApiConfiguration(ContainerBuilder $container): void
    {
        $container->setAlias(ApiConnectionProviderInterface::class, DotEnvApiConnectionProvider::class);
    }

    private function processCategoryConfiguration(ContainerBuilder $container, array $config): void
    {
        // If CategoryConfigurationProvider configuration is not set, use default DatabaseCategoryConfigurationProvider
        if (\count($config) !== 0 && !\array_key_exists('category_configuration', $config)) {
            $container->setAlias(CategoryConfigurationProviderInterface::class, DatabaseCategoryConfigurationProvider::class);

            return;
        }

        // If CategoryConfigurationProvider configuration is set, remove the DatabaseCategoryConfigurationProvider
        $container->removeDefinition(DatabaseCategoryConfigurationProvider::class);
        $container->removeDefinition(AdminCategoryMenuListener::class);
        $container->removeDefinition(CategoriesController::class);

        $categoryConfigurationProviderDefinition = $container->getDefinition(CategoryConfigurationProvider::class);
        $categoryConfigurationProviderDefinition
            ->setArgument('$categoryCodesToImport', $config['category_configuration']['root_category_codes'])
            ->setArgument('$categoryCodesToExclude', $config['category_configuration']['excluded_category_codes'])
            ->setArgument('$useAkeneoPositions', $config['category_configuration']['use_akeneo_positions'])
        ;

        $container->setAlias(CategoryConfigurationProviderInterface::class, CategoryConfigurationProvider::class);
    }

    private function processLocaleMapping(ContainerBuilder $container, array $config): void
    {
        $localeMappingConfigurationDefinition = $container->getDefinition(LocaleMappingConfiguration::class);
        $localeMappingConfigurationDefinition
            ->setArgument('$localeMapping', $config['locale_mappings'])
        ;

        $container->setAlias(LocaleMappingConfigurationInterface::class, LocaleMappingConfiguration::class);
    }

    private function processExcludedAttributes(ContainerBuilder $container, array $config): void
    {
        $localeMappingConfigurationDefinition = $container->getDefinition(ExcludedAttributesConfiguration::class);
        $localeMappingConfigurationDefinition
            ->setArgument('$excludedAttributeCodes', $config['excluded_product_attributes'])
        ;

        $container->setAlias(ExcludedAttributesConfigurationInterface::class, ExcludedAttributesConfiguration::class);
    }
}
