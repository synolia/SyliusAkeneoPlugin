<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute;

use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\SetupAttributeTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\TearDownAttributeTask;

/**
 * @internal
 * @coversNothing
 */
final class RetrieveAttributesTaskTest extends AbstractTaskTest
{
    public function testGetAttributes(): void
    {
        $initialPayload = new AttributePayload($this->createClient());
        $setupAttributeTask = $this->taskProvider->get(SetupAttributeTask::class);
        $setupPayload = $setupAttributeTask->__invoke($initialPayload);

        /** @var RetrieveAttributesTask $retrieveTask */
        $retrieveTask = $this->taskProvider->get(RetrieveAttributesTask::class);
        $payload = $retrieveTask->__invoke($setupPayload);
        $this->assertInstanceOf(AttributePayload::class, $payload);

        $content = \json_decode($this->getFileContent('attributes_all.json'), true);
        $this->assertEquals(\count($content['_embedded']['items']), $this->countAttributes());

        $tearDownAttributeTask = $this->taskProvider->get(TearDownAttributeTask::class);
        $tearDownAttributeTask->__invoke($payload);
    }

    private function countAttributes(): int
    {
        $query = $this->manager->getConnection()->prepare(\sprintf(
            'SELECT count(id) FROM `%s`',
            AttributePayload::TEMP_AKENEO_TABLE_NAME
        ));
        $query->executeStatement();

        return (int) \current($query->fetch());
    }
}
