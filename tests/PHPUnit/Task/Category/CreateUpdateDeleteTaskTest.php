<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Category;

use donatj\MockWebServer\MockWebServer;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoCategoryResourcesException;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Category\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;

final class CreateUpdateDeleteTaskTest extends AbstractTaskTest
{
    private const SAMPLE_PATH = '/datas/sample/';

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    /** @var MockWebServer */
    protected $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
        self::assertInstanceOf(AkeneoTaskProvider::class, $this->taskProvider);
    }

    public function testNoCategories(): void
    {
        $this->expectExceptionObject(new NoCategoryResourcesException('No resource found.'));
        $payload = new CategoryPayload($this->createClient());

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);
    }

    public function testMatchWithCategories(): void
    {
        $initialPayload = new CategoryPayload($this->createClient());
        /** @var RetrieveCategoriesTask $retrieveTask */
        $retrieveTask = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $payload = $retrieveTask->__invoke($initialPayload);

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);
    }
}
