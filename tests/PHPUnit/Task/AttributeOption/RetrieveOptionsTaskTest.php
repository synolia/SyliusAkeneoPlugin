<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\AttributeOption;

use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\Option\OptionsPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask;

/**
 * @internal
 * @coversNothing
 */
final class RetrieveOptionsTaskTest extends AbstractTaskTest
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = $this->getContainer()->get(AkeneoTaskProvider::class);
    }

    public function testGetOptions(): void
    {
        $attributesPayload = new AttributePayload($this->createClient());

        $importAttributePipeline = self::$container->get(AttributePipelineFactory::class)->create();
        $attributesPayload = $importAttributePipeline->process($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask $task */
        $task = $this->taskProvider->get(RetrieveOptionsTask::class);

        /** @var OptionsPayload $optionPayload */
        $optionPayload = $task->__invoke($attributesPayload);

        $options = \json_decode($this->getFileContent('attributes_for_options.json'), true);
        $optionCount = \count($options['_embedded']['items']);

        $this->assertCount($optionCount, $optionPayload->getResources());
    }
}
