<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductOption;

use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\CreateUpdateTask;

final class CreateUpdateTaskTest extends AbstractTaskTest
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

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
        $this->createConfiguration();
        $attributesPayload = new AttributePayload($this->createClient());

        $importAttributePipeline = self::$container->get(AttributePipelineFactory::class)->create();
        $attributesPayload = $importAttributePipeline->process($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask $retrieveOptionsTask */
        $retrieveOptionsTask = $this->taskProvider->get(RetrieveOptionsTask::class);
        $optionsPayload = $retrieveOptionsTask->__invoke($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask $createUpdateDeleteAttributeOptionTask */
        $createUpdateDeleteAttributeOptionTask = $this->taskProvider->get(CreateUpdateDeleteTask::class);
        $attributeOptionPayload = $createUpdateDeleteAttributeOptionTask->__invoke($optionsPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\Option\CreateUpdateTask $createUpdateOptionTask */
        $createUpdateOptionTask = $this->taskProvider->get(CreateUpdateTask::class);
        $createUpdateOptionTask->__invoke($attributeOptionPayload);

        $productOptionRepository = self::$container->get('sylius.repository.product_option');
        /** @var \Sylius\Component\Product\Model\ProductOptionInterface $productOption */
        $productOption = $productOptionRepository->findOneBy(['code' => 'color']);
        $this->assertNotNull($productOption);
    }

    private function createConfiguration(): void
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $apiConfiguration = new ApiConfiguration();
        $apiConfiguration->setBaseUrl('');
        $apiConfiguration->setApiClientId('');
        $apiConfiguration->setApiClientSecret('');
        $apiConfiguration->setPaginationSize(100);
        $apiConfiguration->setIsEnterprise(true);
        $apiConfiguration->setUsername('');
        $apiConfiguration->setPassword('');
        $entityManager->persist($apiConfiguration);
        $entityManager->flush();
    }
}
