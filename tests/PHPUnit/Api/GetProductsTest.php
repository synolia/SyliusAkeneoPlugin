<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api;

use Akeneo\Pim\ApiClient\Api\ProductApi;
use Akeneo\Pim\ApiClient\Pagination\Page;
use donatj\MockWebServer\RequestInfo;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class GetProductsTest extends ApiTestCase
{
    public function testGetProducts(): void
    {
        $this->server->setResponseOfPath(
            '/' . ProductApi::PRODUCTS_URI,
            new ResponseStack(
                new Response($this->getProducts(), [], HttpResponse::HTTP_OK),
                new Response($this->getProducts(), [], HttpResponse::HTTP_OK)
            )
        );

        $api = $this->createClient()->getProductApi();
        /** @var Page $page */
        $page = $api->listPerPage();

        /** @var RequestInfo $lastRequest */
        $lastRequest = $this->server->getLastRequest();
        Assert::assertInstanceOf(RequestInfo::class, $lastRequest);
        Assert::assertSame($lastRequest->jsonSerialize()[RequestInfo::JSON_KEY_METHOD], 'GET');
        Assert::assertInstanceOf(Page::class, $page);
        Assert::assertCount(3, $page->getItems());
        Assert::assertSame('top', $page->getItems()[0]['identifier']);
    }

    private function getProducts(): string
    {
        return $this->getFileContent('products.json');
    }
}
