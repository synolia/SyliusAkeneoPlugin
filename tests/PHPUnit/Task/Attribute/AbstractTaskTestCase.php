<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute;

use Akeneo\Pim\ApiClient\Api\AttributeApi;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApi;
use Akeneo\Pim\ApiClient\Api\FamilyApi;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApi;
use Akeneo\Pim\ApiClient\Api\LocaleApi;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityRecordApi;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

abstract class AbstractTaskTestCase extends ApiTestCase
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\TaskProvider */
    protected $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = self::getContainer()->get('doctrine')->getManager();

        $this->initSyliusLocales();

        $this->manager->flush();

        $this->server->setResponseOfPath(
            '/' . sprintf(LocaleApi::LOCALES_URI),
            new Response($this->getLocales(), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeApi::ATTRIBUTES_URI),
            new Response($this->getFileContent('attributes_for_options.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeOptionApi::ATTRIBUTE_OPTIONS_URI, 'clothing_size'),
            new Response($this->getFileContent('attribute_options_clothing_size.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeOptionApi::ATTRIBUTE_OPTIONS_URI, 'collection'),
            new Response($this->getFileContent('attribute_options_collection.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeOptionApi::ATTRIBUTE_OPTIONS_URI, 'color'),
            new Response($this->getFileContent('attribute_options_color.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityRecordApi::REFERENCE_ENTITY_RECORDS_URI, 'coloris'),
            new Response($this->getFileContent('reference_entity_coloris_records.json'), [], HttpResponse::HTTP_OK),
        );
        $this->server->setResponseOfPath(
            '/' . FamilyApi::FAMILIES_URI,
            new Response($this->getFileContent('families.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANTS_URI, 'clothing'),
            new Response($this->getFileContent('family_clothing_variants.json'), [], HttpResponse::HTTP_OK),
        );

        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
    }

    protected function tearDown(): void
    {
        $this->server->stop();
        $this->manager->close();
        $this->manager = null;

        parent::tearDown();
    }

    protected function getAttributes(): string
    {
        return $this->getFileContent('attributes_all.json');
    }

    protected function getLocales(): string
    {
        return $this->getFileContent('locales.json');
    }
}
