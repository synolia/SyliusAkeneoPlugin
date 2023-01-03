<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Category;

use Akeneo\Pim\ApiClient\Api\CategoryApi;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

abstract class AbstractTaskTest extends ApiTestCase
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\TaskProvider */
    protected $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = $this->getContainer()->get('doctrine')->getManager();

        if (!$this->manager->getConnection()->isTransactionActive()) {
            $this->manager->beginTransaction();
        }

        $this->manager->flush();

        $this->server->setResponseOfPath(
            '/' . sprintf(CategoryApi::CATEGORIES_URI),
            new ResponseStack(
                new Response($this->getCategories(), [], HttpResponse::HTTP_OK),
            ),
        );

        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
    }

    protected function tearDown(): void
    {
        if ($this->manager->getConnection()->isTransactionActive()) {
            $this->manager->rollback();
        }
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
        $categoryConfiguration = $this->manager->getRepository(CategoryConfiguration::class)->findOneBy([]);

        if ($categoryConfiguration instanceof CategoryConfiguration) {
            return $categoryConfiguration;
        }

        $categoryConfiguration = new CategoryConfiguration();
        $categoryConfiguration->setRootCategories(['master']);
        $this->manager->persist($categoryConfiguration);

        return $categoryConfiguration;
    }
}
