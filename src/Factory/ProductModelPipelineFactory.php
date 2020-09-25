<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddOrUpdateProductModelTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\EnableDisableProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\RetrieveProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\SetupProductTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\TearDownProductTask;

final class ProductModelPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(SetupProductTask::class))
            ->pipe($this->taskProvider->get(RetrieveProductModelsTask::class))
            ->pipe($this->taskProvider->get(AddOrUpdateProductModelTask::class))
            ->pipe($this->taskProvider->get(EnableDisableProductModelsTask::class))
            ->pipe($this->taskProvider->get(TearDownProductTask::class))
        ;
    }
}
