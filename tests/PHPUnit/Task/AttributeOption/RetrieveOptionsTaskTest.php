<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\AttributeOption;

use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;

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

        $importAttributePipeline = $this->getContainer()->get(AttributePipelineFactory::class)->create();
        $optionPayload = $importAttributePipeline->process($attributesPayload);

        $this->assertCount(3, $optionPayload->getSelectOptionsResources());
        $this->assertCount(1, $optionPayload->getReferenceEntityOptionsResources());
    }
}
