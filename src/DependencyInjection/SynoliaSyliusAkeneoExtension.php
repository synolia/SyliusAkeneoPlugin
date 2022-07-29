<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Synolia\SyliusAkeneoPlugin\Menu\AdminApiConfigurationMenuListener;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\DatabaseApiConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\DotEnvApiConnectionProvider;

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

        $this->processApiConfiguration($container, $config);
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
        if (\count($config) === 0 || (array_key_exists(0, $config) && !\array_key_exists('api_configuration', $config[0]))) {
            return;
        }

        // If DotEnvApiConnectionProvider configuration is set, remove the DatabaseApiConfigurationProvider
        $container->removeDefinition(DatabaseApiConfigurationProvider::class);
        $container->removeDefinition(AdminApiConfigurationMenuListener::class);

        $dotEnvDefinition = $container->getDefinition(DotEnvApiConnectionProvider::class);
        $dotEnvDefinition
            ->setArgument('$baseUrl', $config['api_configuration']['base_url'])
            ->setArgument('$clientId', $config['api_configuration']['client_id'])
            ->setArgument('$clientSecret', $config['api_configuration']['client_secret'])
            ->setArgument('$username', $config['api_configuration']['username'])
            ->setArgument('$password', $config['api_configuration']['password'])
            ->setArgument('$edition', $config['api_configuration']['edition'])
            ->setArgument('$pagination', $config['api_configuration']['pagination'])
        ;

        $container->setAlias(ApiConnectionProviderInterface::class, DotEnvApiConnectionProvider::class);
    }
}
