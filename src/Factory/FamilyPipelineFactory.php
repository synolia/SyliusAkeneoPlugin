<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Category\MatchPimCodeWithEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;
use Synolia\SyliusAkeneoPlugin\Task\Family\CreateFamiliesTask;
use Synolia\SyliusAkeneoPlugin\Task\Family\CreateFamilyAttributeRelationsTask;
use Synolia\SyliusAkeneoPlugin\Task\Family\InitDefaultGroupsTask;

final class FamilyPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveCategoriesTask::class))
            ->pipe($this->taskProvider->get(MatchPimCodeWithEntityTask::class))
            ->pipe($this->taskProvider->get(CreateFamiliesTask::class))
            ->pipe($this->taskProvider->get(CreateFamilyAttributeRelationsTask::class))
            ->pipe($this->taskProvider->get(\Synolia\SyliusAkeneoPlugin\Task\Family\CountOfChildCategoriesTask::class))
            ->pipe($this->taskProvider->get(InitDefaultGroupsTask::class))
            ;
    }
}
