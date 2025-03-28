<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Filter;

use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Enum\ProductFilterStatusEnum;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleAdvancedType;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleSimpleType;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Webmozart\Assert\Assert;

final class ProductFilter implements ProductFilterInterface
{
    private const AT_LEAST_COMPLETE = 'AT LEAST COMPLETE';

    private const ALL_COMPLETE = 'ALL COMPLETE';

    private const FULL_COMPLETE = 100;

    private const API_DATETIME_FORMAT = 'Y-m-d H:i:s';

    private const AVAILABLE_PRODUCT_MODEL_QUERIES = [
        'created',
        'updated',
        'completeness',
        'categories',
        'family',
        'identifier',
        'enabled',
        'groups',
        'parent',
        'quality_scores',
        'completenesses',
        'code',
    ];

    public function __construct(
        private EntityRepository $productFiltersRulesRepository,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
    ) {
    }

    public function getProductModelFilters(): array
    {
        /** @var ProductFiltersRules $productFilterRules */
        $productFilterRules = $this->productFiltersRulesRepository->findOneBy([], ['id' => 'DESC']);
        if (!$productFilterRules instanceof ProductFiltersRules) {
            return [];
        }

        if (ProductFilterRuleAdvancedType::MODE === $productFilterRules->getMode() && !in_array($productFilterRules->getAdvancedFilter(), [null, '', '0'], true)) {
            return $this->getAdvancedFilter($productFilterRules, true);
        }

        $queryParameters = [];
        if (ProductFilterRuleSimpleType::MODE === $productFilterRules->getMode()) {
            $queryParameters = new SearchBuilder();

            $queryParameters = $this->getUpdatedFilter($productFilterRules, $queryParameters);

            $completeness = self::AT_LEAST_COMPLETE;
            if (self::FULL_COMPLETE === $productFilterRules->getCompletenessValue()) {
                $completeness = self::ALL_COMPLETE;
            }
            $this->getCompletenessFilter($productFilterRules, $queryParameters, $completeness);

            $queryParameters = $this->getExcludeFamiliesFilter($productFilterRules, $queryParameters);
            $queryParameters = $queryParameters->getFilters();
            $queryParameters = ['search' => $queryParameters, 'scope' => $productFilterRules->getChannel()];
        }

        return $queryParameters;
    }

    public function getProductFilters(): array
    {
        /** @var ProductFiltersRules $productFilterRules */
        $productFilterRules = $this->productFiltersRulesRepository->findOneBy([], ['id' => 'DESC']);
        if (!$productFilterRules instanceof ProductFiltersRules) {
            return [];
        }

        if (ProductFilterRuleAdvancedType::MODE === $productFilterRules->getMode() && !in_array($productFilterRules->getAdvancedFilter(), [null, '', '0'], true)) {
            return $this->getAdvancedFilter($productFilterRules);
        }

        $queryParameters = [];
        if (ProductFilterRuleSimpleType::MODE === $productFilterRules->getMode()) {
            $queryParameters = new SearchBuilder();

            $queryParameters = $this->getUpdatedFilter($productFilterRules, $queryParameters);

            $this->getCompletenessFilter(
                $productFilterRules,
                $queryParameters,
                $productFilterRules->getCompletenessType(),
                $productFilterRules->getCompletenessValue(),
            );
            $queryParameters = $this->getExcludeFamiliesFilter($productFilterRules, $queryParameters);
            $queryParameters = $this->getStatus($productFilterRules, $queryParameters);
            $queryParameters = $queryParameters->getFilters();
            $queryParameters = ['search' => $queryParameters, 'scope' => $productFilterRules->getChannel()];
        }

        return $queryParameters;
    }

    private function getStatus(ProductFiltersRules $productFilterRules, SearchBuilder $queryParameters): SearchBuilder
    {
        $status = $productFilterRules->getStatus();
        if (ProductFilterStatusEnum::NO_CONDITION === $status) {
            return $queryParameters;
        }

        return $queryParameters->addFilter(
            'enabled',
            Operator::EQUAL,
            ProductFilterStatusEnum::ENABLED === $status,
        );
    }

    private function getAdvancedFilter(
        ProductFiltersRules $productFilterRules,
        bool $isProductModelFilter = false,
    ): array {
        if (null === $productFilterRules->getAdvancedFilter()) {
            return [];
        }

        parse_str($productFilterRules->getAdvancedFilter(), $advancedFilter);
        if (!\array_key_exists('search', $advancedFilter)) {
            return $advancedFilter;
        }

        Assert::string($advancedFilter['search']);

        $advancedFilter['search'] = json_decode($advancedFilter['search'], true, 512, \JSON_THROW_ON_ERROR);
        if ($isProductModelFilter) {
            return $this->getProductModelAdvancedFilter($advancedFilter);
        }

        return $advancedFilter;
    }

    private function getProductModelCompletenessTypeAdvancedFilter(array $filter): array
    {
        $filter['search']['completeness'][0]['operator'] = self::AT_LEAST_COMPLETE;
        if (self::FULL_COMPLETE === $filter['search']['completeness'][0]['value']) {
            $filter['search']['completeness'][0]['operator'] = self::ALL_COMPLETE;
        }
        unset($filter['search']['completeness'][0]['value']);

        return $filter;
    }

    private function getProductModelAdvancedFilter(array $advancedFilter): array
    {
        $advancedFilter['search'] = array_filter($advancedFilter['search'], static fn (string $key): bool => \in_array($key, self::AVAILABLE_PRODUCT_MODEL_QUERIES), \ARRAY_FILTER_USE_KEY);

        if (\array_key_exists('completeness', $advancedFilter['search']) && \is_array($advancedFilter['search']['completeness'])) {
            $advancedFilter = $this->getProductModelCompletenessTypeAdvancedFilter($advancedFilter);
        }

        return $advancedFilter;
    }

    private function getUpdatedFilter(
        ProductFiltersRules $productFilterRules,
        SearchBuilder $queryParameters,
    ): SearchBuilder {
        $updatedMode = $productFilterRules->getUpdatedMode();
        if (Operator::GREATER_THAN === $updatedMode) {
            $queryParameters->addFilter(
                'updated',
                $updatedMode,
                $productFilterRules->getUpdatedAfter()->format(self::API_DATETIME_FORMAT),
            );
        }
        if (Operator::LOWER_THAN === $updatedMode) {
            $queryParameters->addFilter(
                'updated',
                $updatedMode,
                $productFilterRules->getUpdatedBefore()->format(self::API_DATETIME_FORMAT),
            );
        }
        if (Operator::BETWEEN === $updatedMode) {
            $queryParameters->addFilter(
                'updated',
                $updatedMode,
                [
                    $productFilterRules->getUpdatedBefore()->format(self::API_DATETIME_FORMAT),
                    $productFilterRules->getUpdatedAfter()->format(self::API_DATETIME_FORMAT),
                ],
            );
        }
        if (Operator::SINCE_LAST_N_DAYS === $updatedMode) {
            $queryParameters->addFilter(
                'updated',
                $updatedMode,
                $productFilterRules->getUpdated(),
            );
        }

        return $queryParameters;
    }

    private function getExcludeFamiliesFilter(
        ProductFiltersRules $productFilterRules,
        SearchBuilder $queryParameters,
    ): SearchBuilder {
        if ($productFilterRules->getExcludeFamilies() === []) {
            return $queryParameters;
        }

        return $queryParameters->addFilter(
            'family',
            Operator::NOT_IN,
            $productFilterRules->getExcludeFamilies(),
        );
    }

    private function getLocales(ProductFiltersRules $productFilterRules): array
    {
        if (
            \in_array($productFilterRules->getCompletenessType(), [
            Operator::LOWER_THAN_ON_ALL_LOCALES,
            Operator::GREATER_THAN_ON_ALL_LOCALES,
            Operator::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            Operator::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            ])
        ) {
            return array_map(fn (string $syliusLocale) => $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($syliusLocale), $productFilterRules->getLocales());
        }

        return $this->syliusAkeneoLocaleCodeProvider->getUsedAkeneoLocales();
    }

    private function getCompletenessFilter(
        ProductFiltersRules $productFilterRules,
        SearchBuilder $queryParameters,
        ?string $completeness,
        ?int $completenessValue = null,
    ): SearchBuilder {
        $completenessType = $productFilterRules->getCompletenessType();
        if (null === $completeness || null === $completenessType) {
            return $queryParameters;
        }

        if (
            \in_array($completenessType, [
            Operator::LOWER_THAN_ON_ALL_LOCALES,
            Operator::GREATER_THAN_ON_ALL_LOCALES,
            Operator::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            Operator::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            ])
        ) {
            $queryParameters->addFilter(
                'completeness',
                $completeness,
                $completenessValue,
                [
                    'locales' => $productFilterRules->getLocales(),
                    'scope' => $productFilterRules->getChannel(),
                ],
            );

            return $queryParameters;
        }

        $queryParameters->addFilter(
            'completeness',
            $completeness,
            $completenessValue,
            [
                'locales' => \in_array($completeness, [self::AT_LEAST_COMPLETE, self::ALL_COMPLETE]) ? $this->getLocales($productFilterRules) : [],
                'scope' => $productFilterRules->getChannel(),
            ],
        );

        return $queryParameters;
    }
}
