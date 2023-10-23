<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;

/**
 * See how to configure search filters here:
 * https://api.akeneo.com/documentation/filter.html#filter-categories
 *
 * Keep in mind that the akeneo_category_configuration fixture *must* be configured
 * and ran before this one.
 * Also, if you want to filter some category,
 * don't forget to add all their parents or they won't be imported.
 */
final class CategoriesFixture extends AbstractFixture
{
    public function __construct(
        private CategoryPipelineFactory $categoryPipelineFactory,
        private PayloadFactoryInterface $payloadFactory,
    ) {
    }

    /**
     * @param array{custom: array<string, mixed>} $options
     */
    public function load(array $options): void
    {
        $pipeline = $this->categoryPipelineFactory->create();
        $payload = $this->payloadFactory->create(
            CategoryPayload::class,
        );
        $payload->setCustomFilters($options['custom']);

        $pipeline->process($payload);
    }

    public function getName(): string
    {
        return 'akeneo_categories';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->arrayNode('custom')
                    ->variablePrototype()->end()
                ->end()
            ->end()
        ;
    }
}
