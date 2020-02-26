<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Category;

use Akeneo\Pim\ApiClient\Api\CategoryApi;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

abstract class AbstractTaskTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->server->setResponseOfPath(
            '/' . sprintf(CategoryApi::CATEGORIES_URI),
            new ResponseStack(
                new Response($this->getCategories(), [], HttpResponse::HTTP_OK)
            )
        );
    }

    protected function tearDown(): void
    {
        $this->server->stop();
        parent::tearDown();
    }

    protected function getCategories(): string
    {
        return $this->getFileContent('categories_all.json');
    }
}
