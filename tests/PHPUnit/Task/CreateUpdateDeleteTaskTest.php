<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoCategoryResourcesException;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Category\CreateUpdateDeleteEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;

final class CreateUpdateDeleteTaskTest extends KernelTestCase
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
        self::assertInstanceOf(AkeneoTaskProvider::class, $this->taskProvider);
    }

    public function testNoCategories(): void
    {
        $this->expectExceptionObject(new NoCategoryResourcesException('No resource found.'));
        $akeneoClientFactory = self::$container->get(ClientFactory::class);
        $payload = new CategoryPayload($akeneoClientFactory->createFromApiCredentials());

        /** @var CreateUpdateDeleteEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateDeleteEntityTask::class);
        $task->__invoke($payload);
    }

    public function testMatchWithCategories(): void
    {
        $akeneoClientFactory = self::$container->get(ClientFactory::class);
        $initialPayload = new CategoryPayload($akeneoClientFactory->createFromApiCredentials());
        /** @var RetrieveCategoriesTask $retrieveTask */
        $retrieveTask = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $payload = $retrieveTask->__invoke($initialPayload);

        /** @var RetrieveCategoriesTask $task */
        $task = $this->taskProvider->get(CreateUpdateDeleteEntityTask::class);
        $task->__invoke($payload);
    }
}
