<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute;

use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;

final class RetrieveAttributesTaskTest extends AbstractTaskTest
{
    public function testGetAttributes(): void
    {
        $retrieveAttributePayload = new AttributePayload($this->createClient());

        /** @var \Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask $task */
        $task = $this->taskProvider->get(RetrieveAttributesTask::class);

        /** @var AttributePayload $payload */
        $payload = $task->__invoke($retrieveAttributePayload);
        $this->assertInstanceOf(AttributePayload::class, $payload);

        $content = \json_decode($this->getFileContent('attributes_all.json'), true);
        $this->assertCount(\count($content['_embedded']['items']), $payload->getResources());
    }
}
