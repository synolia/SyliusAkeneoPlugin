<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

/**
 * @deprecated To be removed in 4.0.
 */
final class ApiConfigurationFixture extends AbstractFixture
{
    private EntityManagerInterface $entityManager;

    private ClientFactoryInterface $clientFactory;

    private FactoryInterface $apiConfigurationFactory;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        EntityManagerInterface $entityManager,
        FactoryInterface $apiConfigurationFactory
    ) {
        $this->clientFactory = $clientFactory;
        $this->entityManager = $entityManager;
        $this->apiConfigurationFactory = $apiConfigurationFactory;
    }

    public function load(array $options): void
    {
        /** @var ApiConfiguration $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationFactory->createNew();
        $apiConfiguration->setBaseUrl($options['base_url']);
        $apiConfiguration->setApiClientId($options['api_client_id']);
        $apiConfiguration->setApiClientSecret($options['api_client_secret']);
        $apiConfiguration->setUsername($options['username']);
        $apiConfiguration->setPassword($options['password']);
        $apiConfiguration->setPaginationSize($options['pagination_size']);
        $apiConfiguration->setIsEnterprise($options['is_enterprise']);

        if (null !== $options['edition']) {
            $apiConfiguration->setEdition($options['edition']);
        }

        $client = $this->clientFactory->authenticateByPassword($apiConfiguration);
        $client->getCategoryApi()->all(1);

        $this->entityManager->persist($apiConfiguration);
        $this->entityManager->flush();
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
                ->scalarNode('username')->end()
                ->scalarNode('password')->end()
                ->scalarNode('api_client_id')->end()
                ->scalarNode('api_client_secret')->end()
                ->integerNode('pagination_size')->defaultValue(100)->end()
                ->booleanNode('is_enterprise')->setDeprecated('The "is_enterprise" option is deprecated. Use "edition" instead.')->defaultFalse()->end()
                ->scalarNode('edition')->defaultNull()->end()
            ->end()
        ;
    }
}
