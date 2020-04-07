<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product;

use Akeneo\Pim\ApiClient\Api\AttributeApi;
use Akeneo\Pim\ApiClient\Api\ProductApi;
use Akeneo\Pim\ApiClient\Api\ProductMediaFileApi;
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

        $this->manager = self::$container->get('doctrine')->getManager();
        $this->manager->beginTransaction();

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeApi::ATTRIBUTES_URI),

            new ResponseStack(
                new Response($this->getFileContent('attributes_all.json'), [], HttpResponse::HTTP_OK)
            )
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ProductApi::PRODUCTS_URI),
            new ResponseStack(
                new Response($this->getFileContent('products_all.json'), [], HttpResponse::HTTP_OK)
            )
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ProductMediaFileApi::MEDIA_FILE_DOWNLOAD_URI, '6/3/5/c/635cbfe306a1c13867fe7671c110ee3333fcba13_bag.jpg'),
            new ResponseStack(
                new Response($this->getFileContent('product_1111111171.jpg'), [], HttpResponse::HTTP_OK)
            )
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
}
