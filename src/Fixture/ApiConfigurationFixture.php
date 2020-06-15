<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

class ApiConfigurationFixture extends AbstractFixture
{
    /** @var \Doctrine\Common\Persistence\ObjectManager */
    private $objectManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Client\ClientFactory */
    private $clientFactory;

    public function __construct(
        ClientFactory $clientFactory,
        ObjectManager $objectManager
    ) {
        $this->clientFactory = $clientFactory;
        $this->objectManager = $objectManager;
    }

    public function load(array $options): void
    {
        $apiConfiguration = new ApiConfiguration();
        $apiConfiguration->setBaseUrl($options['base_url']);
        $apiConfiguration->setApiClientId($options['api_client_id']);
        $apiConfiguration->setApiClientSecret($options['api_client_secret']);
        $apiConfiguration->setUsername($options['user_name']);
        $apiConfiguration->setPassword($options['password']);
        $apiConfiguration->setPaginationSize($options['pagination_size']);
        $apiConfiguration->setIsEnterprise($options['is_enterprise']);

        $client = $this->clientFactory->authenticatedByPassword($apiConfiguration);
        $client->getCategoryApi()->all(1);

        $apiConfiguration->setToken($client->getToken() ?? '');
        $apiConfiguration->setRefreshToken($client->getRefreshToken() ?? '');

        $this->objectManager->persist($apiConfiguration);
        $this->objectManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'akeneo_api_configuration';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->scalarNode('base_url')->end()
                ->scalarNode('user_name')->end()
                ->scalarNode('password')->end()
                ->scalarNode('api_client_id')->end()
                ->scalarNode('api_client_secret')->end()
                ->integerNode('pagination_size')->defaultValue(100)->end()
                ->booleanNode('is_enterprise')->defaultFalse()->end()
            ->end()
        ;
    }
}
