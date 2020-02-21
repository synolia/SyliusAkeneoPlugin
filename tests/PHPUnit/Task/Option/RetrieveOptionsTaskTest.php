<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Option;

use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Option\RetrieveOptionsTask;

final class RetrieveOptionsTaskTest extends AbstractTaskTest
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);

        self::assertInstanceOf(AkeneoTaskProvider::class, $this->taskProvider);
    }

    public function testGetAttributes(): void
    {
        $attributesPayload = new AttributePayload($this->createClient());

        $importAttributePipeline = self::$container->get(AttributePipelineFactory::class)->create();
        $attributesPayload = $importAttributePipeline->process($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\Option\RetrieveOptionsTask $task */
        $task = $this->taskProvider->get(RetrieveOptionsTask::class);

        $task->__invoke($attributesPayload);
    }
}
