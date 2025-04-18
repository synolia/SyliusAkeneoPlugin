<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;

/**
 * See how to configure search filters here:
 * https://api.akeneo.com/documentation/filter.html#filter-attributes
 */
#[AutoconfigureTag('sylius_fixtures.fixture')]
final class AttributesFixture extends AbstractFixture
{
    public function __construct(
        private AttributePipelineFactory $attributePipelineFactory,
        private PayloadFactoryInterface $payloadFactory,
    ) {
    }

    /**
     * @param array{
     *     batch_size: int,
     *     allow_parallel: bool,
     *     max_concurrency: int,
     *     custom: array<mixed>
     * } $options
     */
    public function load(array $options): void
    {
        $pipeline = $this->attributePipelineFactory->create();
        $payload = $this->payloadFactory->create(
            AttributePayload::class,
        );
        $payload->setBatchSize($options['batch_size']);
        $payload->setAllowParallel($options['allow_parallel']);
        $payload->setMaxRunningProcessQueueSize($options['max_concurrency']);

        $payload->setCustomFilters($options['custom']);

        $pipeline->process($payload);
    }

    public function getName(): string
    {
        return 'akeneo_attributes';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->integerNode('batch_size')->defaultValue(100)->end()
                ->booleanNode('allow_parallel')->defaultTrue()->end()
                ->integerNode('max_concurrency')->defaultValue(4)->end()
                ->arrayNode('custom')
                    ->variablePrototype()->end()
                ->end()
            ->end()
        ;
    }
}
