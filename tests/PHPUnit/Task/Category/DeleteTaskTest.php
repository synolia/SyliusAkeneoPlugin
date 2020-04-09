<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Category;

use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Task\Category\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\DeleteEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;

final class DeleteTaskTest extends AbstractTaskTest
{
    public function testRemoveCategories(): void
    {
        $taxonRepository = self::$container->get('sylius.repository.taxon');
        $categoryTaxon = $taxonRepository->findOneBy(['code' => 'category']);
        $this->assertNotNull($categoryTaxon);

        $initialPayload = new CategoryPayload($this->createClient());
        /** @var RetrieveCategoriesTask $retrieveTask */
        $retrieveTask = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $payload = $retrieveTask->__invoke($initialPayload);

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $categoryPayload = $task->__invoke($payload);

        /** @var CreateUpdateEntityTask $removeTask */
        $removeTask = $this->taskProvider->get(DeleteEntityTask::class);
        $removeTask->__invoke($categoryPayload);

        $categoryTaxon = $taxonRepository->findOneBy(['code' => 'category']);
        $this->assertNull($categoryTaxon);
    }
}
