<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Provider;

use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute\AbstractTaskTest;

final class AkeneoAttributeDataProviderTest extends AbstractTaskTest
{
    private const DEFAULT_SCOPE = 'ecommerce';

    /** @var AkeneoAttributeDataProvider */
    private $attributeDataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $akeneoPropertiesProvider = new AkeneoAttributePropertiesProvider($this->createClient());
        $akeneoPropertiesProvider->setLoadsAllAttributesAtOnce(true);
        $this->attributeDataProvider = new AkeneoAttributeDataProvider($akeneoPropertiesProvider);
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
        yield ['value', 'ean', \json_decode('"value"', true), 'fr_FR', 'ecommerce'];
        yield ['value', 'sku', \json_decode('"value"', true), 'en_US', 'ecommerce'];
        yield ['value', 'sku', \json_decode('"value"', true), '', ''];
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
        yield ['600', 'wash_temperature', \json_decode('[
            {
              "locale": null,
              "scope": null,
              "data": "600"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield ['600', 'wash_temperature', \json_decode('[
            {
              "locale": "fr_FR",
              "scope": null,
              "data": "600"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield ['600', 'wash_temperature', \json_decode('[
            {
              "locale": "fr_FR",
              "scope": "ecommerce",
              "data": "600"
            }
          ]', true), 'fr_FR', 'ecommerce'];
        yield ['600', 'wash_temperature', \json_decode('[
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
            $expectedValue,
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
