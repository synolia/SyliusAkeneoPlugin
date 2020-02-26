<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Category\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\DeleteEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;

final class CategoryPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveCategoriesTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateEntityTask::class))
            ->pipe($this->taskProvider->get(DeleteEntityTask::class))
        ;
    }
}
