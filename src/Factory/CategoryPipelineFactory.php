<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Category\AssignValueToAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\CountOfChildCategoriesTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\CreateCategoryEntitiesTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\DetectCategoriesPositionTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\DetectCategoryLevelTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\MatchPimCodeWithEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\UpdateUrlKeysTask;

final class CategoryPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveCategoriesTask::class))
            ->pipe($this->taskProvider->get(MatchPimCodeWithEntityTask::class))
            ->pipe($this->taskProvider->get(DetectCategoryLevelTask::class))
            ->pipe($this->taskProvider->get(DetectCategoriesPositionTask::class))
            ->pipe($this->taskProvider->get(CreateCategoryEntitiesTask::class))
            ->pipe($this->taskProvider->get(AssignValueToAttributesTask::class))
            ->pipe($this->taskProvider->get(CountOfChildCategoriesTask::class))
            ->pipe($this->taskProvider->get(UpdateUrlKeysTask::class))
            ;
    }
}
