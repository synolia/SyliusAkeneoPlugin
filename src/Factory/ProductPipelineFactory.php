<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Product\CreateConfigurableProductEntitiesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\CreateSimpleProductEntitiesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\RetrieveProductsTask;

final class ProductPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveProductsTask::class))
            ->pipe($this->taskProvider->get(CreateSimpleProductEntitiesTask::class))
            ->pipe($this->taskProvider->get(CreateConfigurableProductEntitiesTask::class))
        ;
    }
}
