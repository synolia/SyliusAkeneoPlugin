<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute;

use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;

final class RetrieveAttributesTaskTest extends AbstractTaskTest
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
        $retrieveAttributePayload = new AttributePayload($this->createClient());

        /** @var \Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask $task */
        $task = $this->taskProvider->get(RetrieveAttributesTask::class);

        $task->__invoke($retrieveAttributePayload);
    }
}
