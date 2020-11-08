<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Fixture;

use Akeneo\Pim\ApiClient\Search\Operator;
use DateTimeInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Synolia\SyliusAkeneoPlugin\Enum\ProductFilterStatusEnum;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleAdvancedType;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleSimpleType;

final class ProductFilterConfigurationFixture extends AbstractFixture
{
    /** @var \Doctrine\Common\Persistence\ObjectManager */
    private $objectManager;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productFiltersRulesFactory;

    public function __construct(
        ObjectManager $objectManager,
        FactoryInterface $productFiltersRulesFactory
    ) {
        $this->objectManager = $objectManager;
        $this->productFiltersRulesFactory = $productFiltersRulesFactory;
    }

    public function load(array $options): void
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules $productFilterRules */
        $productFilterRules = $this->productFiltersRulesFactory->createNew();

        $updatedBefore = \DateTime::createFromFormat($options['updated_before_format'], $options['updated_before']);
        $updatedAfter = \DateTime::createFromFormat($options['updated_after_format'], $options['updated_after']);

        if (!$updatedBefore instanceof DateTimeInterface || !$updatedAfter instanceof DateTimeInterface) {
            throw new \LogicException('Invalid updatedBefore or updatedAfter date format.');
        }

        $productFilterRules
            ->setMode($options['mode'])
            ->setChannel($options['akeneo_channel'])
            ->setAdvancedFilter($options['advanced_filters'])
            ->setCompletenessType($options['completeness_type'])
            ->setCompletenessValue($options['completeness_value'])
            ->setStatus($options['status'])
            ->setUpdatedMode($options['updated_mode'])
            ->setUpdatedBefore($updatedBefore)
            ->setUpdatedAfter($updatedAfter)
        ;

        foreach ($options['locales'] as $locale) {
            $productFilterRules->addLocale($locale);
        }

        $this->objectManager->persist($productFilterRules);
        $this->objectManager->flush();
    }

    public function getName(): string
    {
        return 'akeneo_product_filter_configuration';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->scalarNode('akeneo_channel')->isRequired()->end()
                ->enumNode('mode')
                    ->values([
                        ProductFilterRuleSimpleType::MODE,
                        ProductFilterRuleAdvancedType::MODE,
                    ])
                    ->defaultValue(ProductFilterRuleSimpleType::MODE)
                ->end()
                ->scalarNode('advanced_filters')->defaultNull()->end()
                ->scalarNode('completeness_type')->end()
                ->arrayNode('locales')
                    ->scalarPrototype()->defaultValue([])->end()
                ->end()
                ->integerNode('completeness_value')
                    ->min(ProductFilterRuleSimpleType::MIN_COMPLETENESS)
                    ->max(ProductFilterRuleSimpleType::MAX_COMPLETENESS)
                ->end()
                ->enumNode('status')
                    ->values([
                        ProductFilterStatusEnum::NO_CONDITION,
                        ProductFilterStatusEnum::ENABLED,
                        ProductFilterStatusEnum::DISABLED,
                    ])
                ->end()
                ->enumNode('updated_mode')
                    ->values([
                        Operator::LOWER_THAN,
                        Operator::GREATER_THAN,
                        Operator::BETWEEN,
                        Operator::SINCE_LAST_N_DAYS,
                    ])
                ->end()
                ->scalarNode('updated_before')->end()
                ->scalarNode('updated_before_format')->end()
                ->scalarNode('updated_after')->end()
                ->scalarNode('updated_after_format')->end()
                ->arrayNode('excluded_families')
                    ->scalarPrototype()->defaultValue([])->end()
                ->end()
            ->end()
        ;
    }
}
