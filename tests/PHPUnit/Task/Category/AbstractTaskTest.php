<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Category;

use Akeneo\Pim\ApiClient\Api\CategoryApi;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;
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
        $this->manager->beginTransaction();

        $this->initializeApiConfiguration();

        $this->manager->flush();

        $this->server->setResponseOfPath(
            '/' . sprintf(CategoryApi::CATEGORIES_URI),
            new ResponseStack(
                new Response($this->getCategories(), [], HttpResponse::HTTP_OK)
            )
        );

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
    }

    protected function tearDown(): void
    {
        $this->manager->rollback();
        $this->manager->close();
        $this->manager = null;

        $this->server->stop();

        parent::tearDown();
    }

    protected function getCategories(): string
    {
        return $this->getFileContent('categories_all.json');
    }

    protected function buildBasicConfiguration(): CategoryConfiguration
    {
        $categoryConfiguration = new CategoryConfiguration();
        $categoryConfiguration->setRootCategory('master');
        $categoryConfiguration->setMainCategory('master'); //To be deleted
        $this->manager->persist($categoryConfiguration);

        return $categoryConfiguration;
    }
}
