<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute;

use Akeneo\Pim\ApiClient\Api\AttributeApi;
use Akeneo\Pim\ApiClient\Api\LocaleApi;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

abstract class AbstractTaskTest extends ApiTestCase
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    protected $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = self::$container->get('doctrine')->getManager();

        $this->initializeApiConfiguration();

        $this->manager->flush();

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeApi::ATTRIBUTES_URI),
            new Response($this->getAttributes(), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(LocaleApi::LOCALES_URI),
            new Response($this->getLocales(), [], HttpResponse::HTTP_OK)
        );

        $this->taskProvider = $this->getContainer()->get(AkeneoTaskProvider::class);
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
