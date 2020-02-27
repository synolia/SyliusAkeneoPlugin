<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\AttributeOption;

use donatj\MockWebServer\MockWebServer;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask;

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

    public function testCreateUpdateTask(): void
    {
        $attributesPayload = new AttributePayload($this->createClient());

        $importAttributePipeline = self::$container->get(AttributePipelineFactory::class)->create();
        $attributesPayload = $importAttributePipeline->process($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask $retrieveOptionsTask */
        $retrieveOptionsTask = $this->taskProvider->get(RetrieveOptionsTask::class);
        $optionsPayload = $retrieveOptionsTask->__invoke($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask $createUpdateDeleteTask */
        $createUpdateDeleteTask = $this->taskProvider->get(CreateUpdateDeleteTask::class);
        $createUpdateDeleteTask->__invoke($optionsPayload);
    }
}
