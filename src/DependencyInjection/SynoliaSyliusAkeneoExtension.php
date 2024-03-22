<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Synolia\SyliusAkeneoPlugin\Controller\ApiConfigurationController;
use Synolia\SyliusAkeneoPlugin\Controller\CategoriesController;
use Synolia\SyliusAkeneoPlugin\Menu\AdminApiConfigurationMenuListener;
use Synolia\SyliusAkeneoPlugin\Menu\AdminCategoryMenuListener;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\DatabaseApiConfigurationProvider;
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

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yaml');
        $loader->load('processors.yaml');

        $this->processApiConfiguration($container, $config);
        $this->processCategoryConfiguration($container, $config);
        $this->processLocaleMapping($container, $config);
        $this->processExcludedAttributes($container, $config);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('monolog', ['channels' => ['akeneo']]);

        if (!$container->hasExtension('twig')) {
            return;
        }

        $viewsPath = \dirname(__DIR__) . '/Resources/views/';
        // This add our override in twig paths with correct namespace. No need for final user to copy it
        $paths = [
            $viewsPath . 'SyliusAttributeBundle' => 'SyliusAttribute',
        ];

        $container->prependExtensionConfig('twig', [
            'paths' => $paths,
        ]);
        $this->prependDoctrineMigrations($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Synolia\SyliusAkeneoPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@SynoliaSyliusAkeneoPlugin/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return ['Sylius\Bundle\CoreBundle\Migrations'];
    }

    private function processApiConfiguration(ContainerBuilder $container, array $config): void
    {
        // If DotEnvApiConnectionProvider configuration is not set, use default DatabaseApiConfigurationProvider
        if (\count($config) !== 0 && !\array_key_exists('api_configuration', $config)) {
            $container->setAlias(ApiConnectionProviderInterface::class, DatabaseApiConfigurationProvider::class);

            return;
        }

        // If DotEnvApiConnectionProvider configuration is set, remove the DatabaseApiConfigurationProvider
        $container->removeDefinition(DatabaseApiConfigurationProvider::class);
        $container->removeDefinition(AdminApiConfigurationMenuListener::class);
        $container->removeDefinition(ApiConfigurationController::class);

        $dotEnvDefinition = $container->getDefinition(DotEnvApiConnectionProvider::class);
        $dotEnvDefinition
            ->setArgument('$baseUrl', $config['api_configuration']['base_url'])
            ->setArgument('$clientId', $config['api_configuration']['client_id'])
            ->setArgument('$clientSecret', $config['api_configuration']['client_secret'])
            ->setArgument('$username', $config['api_configuration']['username'])
            ->setArgument('$password', $config['api_configuration']['password'])
            ->setArgument('$edition', $config['api_configuration']['edition'])
            ->setArgument('$axeAsModel', $config['api_configuration']['axe_as_model'])
            ->setArgument('$pagination', $config['api_configuration']['pagination'])
        ;

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
            ->setArgument('$excludedAttributeCodes', $config['excluded_attributes'])
        ;

        $container->setAlias(ExcludedAttributesConfigurationInterface::class, ExcludedAttributesConfiguration::class);
    }
}
