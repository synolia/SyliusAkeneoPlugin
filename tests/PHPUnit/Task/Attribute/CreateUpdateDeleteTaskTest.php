<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute;

use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\DeleteEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;

final class CreateUpdateDeleteTaskTest extends AbstractTaskTest
{
    public function testNoAttributes(): void
    {
        $attributesCount = self::$container->get('sylius.repository.product_attribute')->count([]);
        $this->expectExceptionObject(new NoAttributeResourcesException('No resource found.'));
        $payload = new AttributePayload($this->createClient());

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);
        $finalAttributeCount = self::$container->get('sylius.repository.product_attribute')->count([]);

        $this->assertEquals($attributesCount, $finalAttributeCount);
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

        /** @var \Sylius\Component\Product\Model\ProductAttribute $careInstructionProductAttribute */
        $careInstructionProductAttribute = self::$container->get('sylius.repository.product_attribute')->findOneBy(['code' => 'care_instructions']);
        $this->assertNotNull($careInstructionProductAttribute);
        $this->assertEquals('Instructions d\'entretien', $careInstructionProductAttribute->getTranslation('fr_FR')->getName());
        $this->assertEquals('Care instructions', $careInstructionProductAttribute->getTranslation('en_US')->getName());

        /** @var \Sylius\Component\Product\Model\ProductAttribute $colorProductAttribute */
        $colorProductAttribute = self::$container->get('sylius.repository.product_attribute')->findOneBy(['code' => 'color']);
        $this->assertNotNull($colorProductAttribute);
        $this->assertEquals('Couleur', $colorProductAttribute->getTranslation('fr_FR')->getName());
        $this->assertEquals('Color', $colorProductAttribute->getTranslation('en_US')->getName());
    }

    public function testDeleteTask(): void
    {
        $oldAttribute = self::$container->get('sylius.repository.product_attribute')->findOneBy([]);
        $this->assertNotNull($oldAttribute);
        $oldAttributeCode = $oldAttribute->getCode();

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
        $oldAttribute = self::$container->get('sylius.repository.product_attribute')->findOneBy(['code' => $oldAttributeCode]);

        $this->assertNull($oldAttribute);
    }
}
