<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute;

use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\SetupAttributeTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\TearDownAttributeTask;

/**
 * @internal
 * @coversNothing
 */
final class CreateUpdateDeleteTaskTest extends AbstractTaskTest
{
    public function testNoAttributes(): void
    {
        $attributesCount = $this->getContainer()->get('sylius.repository.product_attribute')->count([]);
        $payload = new AttributePayload($this->createClient());

        $setupAttributeTask = $this->taskProvider->get(SetupAttributeTask::class);
        $setupPayload = $setupAttributeTask->__invoke($payload);

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($setupPayload);

        $finalAttributeCount = $this->getContainer()->get('sylius.repository.product_attribute')->count([]);

        $this->assertEquals($attributesCount, $finalAttributeCount);
    }

    public function testCreateUpdateTask(): void
    {
        $initialPayload = new AttributePayload($this->createClient());
        $setupAttributeTask = $this->taskProvider->get(SetupAttributeTask::class);
        $setupPayload = $setupAttributeTask->__invoke($initialPayload);

        /** @var RetrieveAttributesTask $retrieveTask */
        $retrieveTask = $this->taskProvider->get(RetrieveAttributesTask::class);
        $payload = $retrieveTask->__invoke($setupPayload);

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);

        $tearDownAttributeTask = $this->taskProvider->get(TearDownAttributeTask::class);
        $tearDownAttributeTask->__invoke($payload);

        /** @var \Sylius\Component\Product\Model\ProductAttribute $careInstructionProductAttribute */
        $careInstructionProductAttribute = $this->getContainer()->get('sylius.repository.product_attribute')->findOneBy(['code' => 'care_instructions']);
        $this->assertNotNull($careInstructionProductAttribute);
        $this->assertEquals('Instructions d\'entretien', $careInstructionProductAttribute->getTranslation('fr_FR')->getName());
        $this->assertEquals('Care instructions', $careInstructionProductAttribute->getTranslation('en_US')->getName());

        /** @var \Sylius\Component\Product\Model\ProductAttribute $colorProductAttribute */
        $colorProductAttribute = $this->getContainer()->get('sylius.repository.product_attribute')->findOneBy(['code' => 'color']);
        $this->assertNotNull($colorProductAttribute);
        $this->assertEquals('Couleur', $colorProductAttribute->getTranslation('fr_FR')->getName());
        $this->assertEquals('Color', $colorProductAttribute->getTranslation('en_US')->getName());
    }
}
