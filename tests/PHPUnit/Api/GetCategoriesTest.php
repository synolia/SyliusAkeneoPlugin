<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api;

use Akeneo\Pim\ApiClient\Api\CategoryApi;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursor;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use donatj\MockWebServer\RequestInfo;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * @internal
 *
 * @coversNothing
 */
final class GetCategoriesTest extends ApiTestCase
{
    public function testGetCategories(): void
    {
        $this->server->setResponseOfPath(
            '/' . sprintf(CategoryApi::CATEGORIES_URI),
            new ResponseStack(
                new Response($this->getCategories(), [], HttpResponse::HTTP_OK),
            ),
        );

        $api = $this->createClient()->getCategoryApi();

        /** @var ResourceCursorInterface $categories */
        $categories = $api->all();

        /** @var RequestInfo $lastRequest */
        $lastRequest = $this->server->getLastRequest();
        Assert::assertInstanceOf(RequestInfo::class, $lastRequest);
        Assert::assertSame($lastRequest->jsonSerialize()[RequestInfo::JSON_KEY_METHOD], 'GET');
        Assert::assertInstanceOf(ResourceCursor::class, $categories);
        Assert::assertSame(json_decode($this->getCategories(), true, 512, \JSON_THROW_ON_ERROR)['_embedded']['items'][0], $categories->current());
    }

    private function getCategories(): string
    {
        return $this->getFileContent('categories.json');
    }
}
