<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Provider;

use Akeneo\Pim\ApiClient\Api\AttributeApi;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute\AbstractTaskTest;

/**
 * @internal
 *
 * @coversNothing
 */
final class AkeneoAttributePropertiesProviderTest extends AbstractTaskTest
{
    private \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider $attributePropertiesProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeApi::ATTRIBUTES_URI),
            new Response($this->getFileContent('attributes_all.json'), [], HttpResponse::HTTP_OK),
        );

        $this->attributePropertiesProvider = new AkeneoAttributePropertiesProvider($this->createClient());
        $this->attributePropertiesProvider->setLoadsAllAttributesAtOnce(true);
    }

    /** @dataProvider attributePropertiesDataProvider */
    public function testGetProperties(string $attributeCode, bool $isNullable, bool $isLocalizable, bool $isScopable): void
    {
        $this->assertEquals($isNullable, $this->attributePropertiesProvider->isUnique($attributeCode));
        $this->assertEquals($isLocalizable, $this->attributePropertiesProvider->isLocalizable($attributeCode));
        $this->assertEquals($isScopable, $this->attributePropertiesProvider->isScopable($attributeCode));
    }

    public function attributePropertiesDataProvider(): \Generator
    {
        yield ['ean', true, false, false];
        yield ['wash_temperature', false, false, false];
        yield ['release_date', false, false, true];
        yield ['variation_description', false, true, false];
        yield ['description', false, true, true];
    }
}
