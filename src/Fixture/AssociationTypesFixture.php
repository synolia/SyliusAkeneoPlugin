<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\Factory\AssociationTypePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;

#[AutoconfigureTag('sylius_fixtures.fixture')]
final class AssociationTypesFixture extends AbstractFixture
{
    public function __construct(
        private AssociationTypePipelineFactory $associationTypePipelineFactory,
        private PayloadFactoryInterface $payloadFactory,
    ) {
    }

    /**
     * @param array{
     *     batch_size: int,
     *     allow_parallel: bool,
     *     max_concurrency: int,
     * } $options
     */
    public function load(array $options): void
    {
        $pipeline = $this->associationTypePipelineFactory->create();
        $payload = $this->payloadFactory->create(
            AssociationTypePayload::class,
        );

        $payload->setBatchSize($options['batch_size']);
        $payload->setAllowParallel($options['allow_parallel']);
        $payload->setMaxRunningProcessQueueSize($options['max_concurrency']);

        $pipeline->process($payload);
    }

    public function getName(): string
    {
        return 'akeneo_association_types';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->integerNode('batch_size')->defaultValue(100)->end()
                ->booleanNode('allow_parallel')->defaultTrue()->end()
                ->integerNode('max_concurrency')->defaultValue(4)->end()
            ->end()
        ;
    }
}
