<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute;

use donatj\MockWebServer\MockWebServer;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\DeleteEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;

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

    protected function tearDown(): void
    {
        $this->server->stop();
        parent::tearDown();
    }

    public function testNoAttributes(): void
    {
        $this->expectExceptionObject(new NoAttributeResourcesException('No resource found.'));
        $payload = new AttributePayload($this->createClient());

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);
    }

    public function testCreateUpdateTask(): void
    {
        $initialPayload = new AttributePayload($this->createClient());
        /** @var RetrieveAttributesTask $retrieveTask */
        $retrieveTask = $this->taskProvider->get(RetrieveAttributesTask::class);
        $payload = $retrieveTask->__invoke($initialPayload);

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);
    }

    public function testDeleteTask(): void
    {
        $initialPayload = new AttributePayload($this->createClient());
        /** @var RetrieveAttributesTask $retrieveTask */
        $retrieveTask = $this->taskProvider->get(RetrieveAttributesTask::class);
        $payload = $retrieveTask->__invoke($initialPayload);

        /** @var CreateUpdateEntityTask $createUpdateTask */
        $createUpdateTask = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $createUpdatePayload = $createUpdateTask->__invoke($payload);

        /** @var DeleteEntityTask $deleteTask */
        $deleteTask = $this->taskProvider->get(DeleteEntityTask::class);
        $deleteTask->__invoke($createUpdatePayload);
    }
}
