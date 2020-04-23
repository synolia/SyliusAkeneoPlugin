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
        /**
         * mugs is part of Sylius fixtures and Akeneo fixtures
         * and category is the parent of mugs so category should not be removed
         */
        $categoriesNotDeleted = ['category', 'mugs'];

        $taxonRepository = self::$container->get('sylius.repository.taxon');
        $queryBuilder = $this->manager->createQueryBuilder();
        $categoriesToDelete = $queryBuilder
            ->select('taxon')
            ->from(self::$container->getParameter('sylius.model.taxon.class'), 'taxon')
            ->where('taxon.code NOT IN (:taxons)')
            ->setParameter('taxons', $categoriesNotDeleted)
            ->getQuery()
            ->getResult()
        ;

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

        foreach ($categoriesNotDeleted as $category) {
            $categoryEntity = $taxonRepository->findOneBy(['code' => $category]);
            $this->assertNotNull($categoryEntity);
        }

        foreach ($categoriesToDelete as $category) {
            $categoryEntity = $taxonRepository->findOneBy(['code' => $category]);
            $this->assertNull($categoryEntity);
        }
    }
}
