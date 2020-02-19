<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;

final class RetrieveCategoriesTaskTest extends KernelTestCase
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

    public function testGetCategories(): void
    {
        $akeneoClientFactory = self::$container->get(ClientFactory::class);
        $retrieveCategoryPayload = new CategoryPayload($akeneoClientFactory->createFromApiCredentials());

        /** @var RetrieveCategoriesTask $task */
        $task = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $task->__invoke($retrieveCategoryPayload);
    }
}
