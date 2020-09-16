<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Provider;

use Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute\AbstractTaskTest;

final class AkeneoAttributeDataProviderTest extends AbstractTaskTest
{
    private const DEFAULT_SCOPE = 'ecommerce';

    /** @var AkeneoAttributeDataProvider */
    private $attributeDataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AkeneoAttributePropertiesProvider $akeneoPropertiesProvider */
        $akeneoPropertiesProvider = self::$container->get(AkeneoAttributePropertiesProvider::class);
        $akeneoPropertiesProvider->setLoadsAllAttributesAtOnce(true);
        $productAttributeValueValueBuilder = self::$container->get(ProductAttributeValueValueBuilder::class);
        $this->attributeDataProvider = new AkeneoAttributeDataProvider($akeneoPropertiesProvider, $productAttributeValueValueBuilder);
    }

    /** @dataProvider uniqueAttributeDataProvider */
    public function testUniqueValue($expectedValue, $attributeCode, $attributeValues, string $locale, string $scope): void
    {
        $this->assertEquals(
            $expectedValue,
            $this->attributeDataProvider->getData($attributeCode, $attributeValues, $locale, $scope)
        );
    }

    public function uniqueAttributeDataProvider(): \Generator
    {
        yield ['1234567890142', 'ean', \json_decode('[
            {
              "locale": null,
              "scope": null,
              "data": "1234567890142"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield ['1234567890142', 'sku', \json_decode('[
            {
              "locale": null,
              "scope": null,
              "data": "1234567890142"
            }
          ]', true), 'en_US', 'ecommerce'];
        yield ['1234567890142', 'sku', \json_decode('[
            {
              "locale": null,
              "scope": null,
              "data": "1234567890142"
            }
          ]', true), '', ''];
    }

    /** @dataProvider nonUniqueNonLocalizableNonScopableAttributeDataProvider */
    public function testNonUniqueNonLocalizableNonScopableValue($expectedValue, $attributeCode, $attributeValues, string $locale, string $scope): void
    {
        $this->assertEquals(
            $expectedValue,
            $this->attributeDataProvider->getData($attributeCode, $attributeValues, $locale, $scope)
        );
    }

    public function nonUniqueNonLocalizableNonScopableAttributeDataProvider(): \Generator
    {
        yield [[CreateUpdateDeleteTask::AKENEO_PREFIX . '600'], 'wash_temperature', \json_decode('[
            {
              "locale": null,
              "scope": null,
              "data": "600"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield [[CreateUpdateDeleteTask::AKENEO_PREFIX . '600'], 'wash_temperature', \json_decode('[
            {
              "locale": "fr_FR",
              "scope": null,
              "data": "600"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield [[CreateUpdateDeleteTask::AKENEO_PREFIX . '600'], 'wash_temperature', \json_decode('[
            {
              "locale": "fr_FR",
              "scope": "ecommerce",
              "data": "600"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield [[CreateUpdateDeleteTask::AKENEO_PREFIX . '600'], 'wash_temperature', \json_decode('[
            {
              "locale": null,
              "scope": "ecommerce",
              "data": "600"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield ['32 INCH', 'display_diagonal', \json_decode('[
            {
              "locale": null,
              "scope": null,
              "data": {
                "amount": 32,
                "unit": "INCH"
              }
            }
          ]', true), 'en_US', 'ecommerce'];
    }

    /** @dataProvider nonUniqueNonLocalizableScopableAttributeDataProvider */
    public function testNonUniqueNonLocalizableScopableValue($expectedValue, $attributeCode, $attributeValues, string $locale, string $scope): void
    {
        $this->assertEquals(
            new \DateTime($expectedValue),
            $this->attributeDataProvider->getData($attributeCode, $attributeValues, $locale, $scope)
        );
    }

    public function nonUniqueNonLocalizableScopableAttributeDataProvider(): \Generator
    {
        yield ['2011-12-02T00:00:00+01:00', 'release_date', \json_decode('[
            {
              "locale": null,
              "scope": "ecommerce",
              "data": "2011-12-02T00:00:00+01:00"
            }
          ]', true), 'fr_FR', 'ecommerce'];
    }

    /** @dataProvider nonUniqueLocalizableScopableAttributeDataProvider */
    public function testNonUniqueLocalizableScopableValue($expectedValue, $attributeCode, $attributeValues, string $locale, string $scope): void
    {
        $this->assertEquals(
            $expectedValue,
            $this->attributeDataProvider->getData($attributeCode, $attributeValues, $locale, $scope)
        );
    }

    public function nonUniqueLocalizableScopableAttributeDataProvider(): \Generator
    {
        yield ['description fr', 'variation_description', \json_decode('[
            {
              "locale": "fr_FR",
              "scope": null,
              "data": "description fr"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield ['description fr', 'variation_description', \json_decode('[
            {
              "locale": "fr_FR",
              "scope": null,
              "data": "description fr"
            },
            {
              "locale": "en_US",
              "scope": null,
              "data": "description en"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield ['description en', 'variation_description', \json_decode('[
            {
              "locale": "fr_FR",
              "scope": null,
              "data": "description fr"
            },
            {
              "locale": "en_US",
              "scope": null,
              "data": "description en"
            }
          ]', true), 'en_US', 'ecommerce'];
        yield ['description en', 'variation_description', \json_decode('[
            {
              "locale": "fr_FR",
              "scope": null,
              "data": "description fr"
            },
            {
              "locale": "en_US",
              "scope": null,
              "data": "description en"
            }
          ]', true), 'en_US', ''];
    }
}
