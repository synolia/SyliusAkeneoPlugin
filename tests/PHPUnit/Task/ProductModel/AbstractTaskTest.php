<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use Akeneo\Pim\ApiClient\Api\ProductModelApi;
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
            '/' . sprintf(ProductModelApi::PRODUCT_MODELS_URI),
            new ResponseStack(
                new Response($this->getFileContent('product_model_all.json'), [], HttpResponse::HTTP_OK)
            )
        );
    }

    protected function tearDown(): void
    {
        $this->server->stop();

        parent::tearDown();
    }
}
