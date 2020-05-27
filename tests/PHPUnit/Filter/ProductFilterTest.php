<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Filter;

use Akeneo\Pim\ApiClient\Api\LocaleApi;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use donatj\MockWebServer\Response;
use PHPUnit\Framework\Assert;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Enum\ProductFilterStatusEnum;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilter;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

final class ProductFilterTest extends ApiTestCase
{
    private const API_DATETIME_FORMAT = 'Y-m-d H:i:s';

    private const COMPLETENESS_ALL_COMPLETE = 'ALL COMPLETE';

    /** @var ProductFiltersRules */
    private $productFiltersRules;

    /** @var ProductFilter */
    private $productFilter;

    /** @var EntityRepository */
    private $localeRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = self::$container->get('doctrine')->getManager();
        $this->manager->beginTransaction();
        $this->localeRepository = self::$container->get('sylius.repository.locale');
        $this->productFilter = self::$container->get(ProductFilter::class);
        $this->syliusAkeneoLocaleCodeProvider = self::$container->get(SyliusAkeneoLocaleCodeProvider::class);

        $this->productFiltersRules = $this->manager->getRepository(ProductFiltersRules::class)->findOneBy([]);
        if (!$this->productFiltersRules instanceof ProductFiltersRules) {
            $this->productFiltersRules = new ProductFiltersRules();
            $this->manager->persist($this->productFiltersRules);
        }
        $this->productFiltersRules
            ->setCompletenessType(Operator::EQUAL)
            ->setCompletenessValue(100)
            ->setChannel('ecommerce')
            ->setMode('simple')
            ->addExcludeFamily('shoes')
            ->addLocale('en_US')
            ->setMode('simple')
            ->setStatus(ProductFilterStatusEnum::NO_CONDITION)
            ->setUpdatedAfter(new \DateTime('2020-04-04'))
            ->setUpdatedBefore(new \DateTime('2020-04-04'))
        ;

        $this->manager->flush();

        $this->server->setResponseOfPath(
            '/' . sprintf(LocaleApi::LOCALES_URI),
            new Response($this->getFileContent('locales.json'), [], HttpResponse::HTTP_OK)
        );
    }

    protected function tearDown(): void
    {
        $this->manager->rollback();
        $this->manager->close();
        $this->manager = null;

        $this->server->stop();

        parent::tearDown();
    }

    public function testGetUpdatedFilter(): void
    {
        $this->productFiltersRules->setUpdatedMode(Operator::GREATER_THAN);
        $reflectionClass = new \ReflectionClass($this->productFilter);
        $method = $reflectionClass->getMethod('getUpdatedFilter');
        $method->setAccessible(true);
        /** @var SearchBuilder $result */
        $result = $method->invoke($this->productFilter, $this->productFiltersRules, new SearchBuilder());
        $this->assertInstanceOf(SearchBuilder::class, $result);
        $expect = [
            'updated' => [
                [
                    'operator' => Operator::GREATER_THAN,
                    'value' => $this->productFiltersRules->getUpdatedAfter()->format(self::API_DATETIME_FORMAT),
                ],
            ],
        ];
        Assert::assertEquals($expect, $result->getFilters());

        $this->productFiltersRules->setUpdatedMode(Operator::LOWER_THAN);
        $result = $method->invoke($this->productFilter, $this->productFiltersRules, new SearchBuilder());
        Assert::assertInstanceOf(SearchBuilder::class, $result);
        $expect = [
            'updated' => [
                [
                    'operator' => Operator::LOWER_THAN,
                    'value' => $this->productFiltersRules->getUpdatedBefore()->format(self::API_DATETIME_FORMAT),
                ],
            ],
        ];
        Assert::assertEquals($expect, $result->getFilters());

        $this->productFiltersRules->setUpdatedMode(Operator::SINCE_LAST_N_DAYS);
        $this->productFiltersRules->setUpdated(4);
        $result = $method->invoke($this->productFilter, $this->productFiltersRules, new SearchBuilder());
        Assert::assertInstanceOf(SearchBuilder::class, $result);
        $expect = [
            'updated' => [
                [
                    'operator' => Operator::SINCE_LAST_N_DAYS,
                    'value' => $this->productFiltersRules->getUpdated(),
                ],
            ],
        ];
        Assert::assertEquals($expect, $result->getFilters());

        $this->productFiltersRules->setUpdatedMode(Operator::BETWEEN);
        $result = $method->invoke($this->productFilter, $this->productFiltersRules, new SearchBuilder());
        Assert::assertInstanceOf(SearchBuilder::class, $result);
        $expect = [
            'updated' => [
                [
                    'operator' => Operator::BETWEEN,
                    'value' => [
                        $this->productFiltersRules->getUpdatedBefore()->format(self::API_DATETIME_FORMAT),
                        $this->productFiltersRules->getUpdatedAfter()->format(self::API_DATETIME_FORMAT),
                    ],
                ],
            ],
        ];
        Assert::assertEquals($expect, $result->getFilters());
    }

    public function testGetFamiliesFilter(): void
    {
        $result = $this->productFilter->getProductFilters();
        Assert::assertIsArray($result['search']);
        Assert::assertArrayHasKey('family', $result['search']);
        $expect = [
            [
                'operator' => Operator::NOT_IN,
                'value' => ['shoes'],
            ],
        ];
        Assert::assertEquals($expect, $result['search']['family']);
    }

    public function testGetCompletenessFilter(): void
    {
        $reflectionClass = new \ReflectionClass($this->productFilter);
        $method = $reflectionClass->getMethod('getCompletenessFilter');
        $method->setAccessible(true);

        $result = $method->invoke(
            $this->productFilter,
            $this->productFiltersRules,
            new SearchBuilder(),
            self::COMPLETENESS_ALL_COMPLETE
        );
        Assert::assertInstanceOf(SearchBuilder::class, $result);
        $expect = [
            'completeness' => [
                [
                    'operator' => self::COMPLETENESS_ALL_COMPLETE,
                    'locales' => $this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms(),
                    'scope' => 'ecommerce',
                ],
            ],
        ];
        Assert::assertEquals($expect, $result->getFilters());

        $this->productFiltersRules->setCompletenessType(Operator::GREATER_THAN_ON_ALL_LOCALES);
        $result = $method->invoke(
            $this->productFilter,
            $this->productFiltersRules,
            new SearchBuilder(),
            Operator::GREATER_THAN_ON_ALL_LOCALES
        );
        Assert::assertInstanceOf(SearchBuilder::class, $result);
        $expect = [
            'completeness' => [
                [
                    'operator' => Operator::GREATER_THAN_ON_ALL_LOCALES,
                    'locales' => ['en_US'],
                    'scope' => 'ecommerce',
                ],
            ],
        ];
        Assert::assertEquals($expect, $result->getFilters());
    }

    public function testGetScopeFilter(): void
    {
        $result = $this->productFilter->getProductFilters();
        Assert::assertIsArray($result);
        Assert::assertArrayHasKey('scope', $result);
        Assert::assertSame('ecommerce', $result['scope']);

        $result = $this->productFilter->getProductModelFilters();
        Assert::assertIsArray($result);
        Assert::assertArrayHasKey('scope', $result);
        Assert::assertSame('ecommerce', $result['scope']);
    }

    public function testGetProductModelFilterWithAdvancedMode(): void
    {
        $this->productFiltersRules->setMode('advanced');
        $this->productFiltersRules->setAdvancedFilter(
            'search={"enabled":[{"operator":"=","value":true}],"completeness":[{"operator":"=","value": 100, "locales":["en_US"], "scope": "ecommerce"}]}&scope=ecommerce'
        );

        $this->manager->flush();

        $result = $this->productFilter->getProductModelFilters();
        Assert::assertIsArray($result);
        $expect = [
            'search' => [
                'completeness' => [
                    [
                        'operator' => self::COMPLETENESS_ALL_COMPLETE,
                        'locales' => ['en_US'],
                        'scope' => 'ecommerce',
                    ],
                ],
            ],
            'scope' => 'ecommerce',
        ];
        Assert::assertEquals($expect, $result);
    }

    public function testGetProductFilterWithAdvancedMode(): void
    {
        $this->productFiltersRules->setMode('advanced');
        $this->productFiltersRules->setAdvancedFilter(
            'search={"enabled":[{"operator":"=","value":true}],"completeness":[{"operator":"=","value": 100, "locales":["en_US"], "scope": "ecommerce"}]}&scope=ecommerce'
        );

        $this->manager->flush();
        $result = $this->productFilter->getProductFilters();
        Assert::assertIsArray($result);
        $expect = [
            'search' => [
                'enabled' => [
                    [
                        'operator' => Operator::EQUAL,
                        'value' => true,
                    ],
                ],
                'completeness' => [
                    [
                        'operator' => Operator::EQUAL,
                        'value' => 100,
                        'locales' => ['en_US'],
                        'scope' => 'ecommerce',
                    ],
                ],
            ],
            'scope' => 'ecommerce',
        ];
        Assert::assertEquals($expect, $result);
    }

    public function testDontApplyStatusFilterWhenRulesStatusIsNull(): void
    {
        $result = $this->productFilter->getProductFilters();
        Assert::assertIsArray($result);
        Assert::assertArrayNotHasKey('enabled', $result);

        $result = $this->productFilter->getProductModelFilters();
        Assert::assertIsArray($result);
        Assert::assertArrayNotHasKey('enabled', $result);
    }

    public function testFilterEnabledProductWhenRulesStatusIsTrue(): void
    {
        $this->productFiltersRules
            ->setStatus(ProductFilterStatusEnum::ENABLED)
        ;
        $this->manager->flush();

        $result = $this->productFilter->getProductFilters();
        Assert::assertIsArray($result['search']);
        Assert::assertArrayHasKey('enabled', $result['search']);
        $expect = [
            [
                'operator' => Operator::EQUAL,
                'value' => true,
            ],
        ];
        Assert::assertEquals($expect, $result['search']['enabled']);

        $result = $this->productFilter->getProductModelFilters();
        Assert::assertIsArray($result['search']);
        Assert::assertArrayNotHasKey('enabled', $result['search']);
    }

    public function testFilterDisabledProductWhenRulesStatusIsFalse(): void
    {
        $this->productFiltersRules
            ->setStatus(ProductFilterStatusEnum::DISABLED)
        ;
        $this->manager->flush();

        $result = $this->productFilter->getProductFilters();
        Assert::assertIsArray($result['search']);
        Assert::assertArrayHasKey('enabled', $result['search']);
        $expect = [
            [
                'operator' => Operator::EQUAL,
                'value' => false,
            ],
        ];
        Assert::assertEquals($expect, $result['search']['enabled']);

        $result = $this->productFilter->getProductModelFilters();
        Assert::assertIsArray($result['search']);
        Assert::assertArrayNotHasKey('enabled', $result['search']);
    }
}
