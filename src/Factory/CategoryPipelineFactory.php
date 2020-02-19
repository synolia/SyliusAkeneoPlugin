<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Category\CreateUpdateDeleteEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\UpdateUrlKeysTask;

final class CategoryPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveCategoriesTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateDeleteEntityTask::class))
            ->pipe($this->taskProvider->get(UpdateUrlKeysTask::class))
        ;
    }
}
