<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\Product\CreateConfigurableProductEntitiesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\CreateSimpleProductEntitiesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\EnableDisableProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\RetrieveProductsTask;

final class ProductPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveProductsTask::class))
            ->pipe($this->taskProvider->get(CreateSimpleProductEntitiesTask::class))
            ->pipe($this->taskProvider->get(EnableDisableProductsTask::class))
            ->pipe($this->taskProvider->get(CreateConfigurableProductEntitiesTask::class))
        ;
    }
}
