<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddOrUpdateProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\DisableProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\RetrieveProductModelsTask;

final class ProductModelPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveProductModelsTask::class))
            ->pipe($this->taskProvider->get(DisableProductModelsTask::class))
            ->pipe($this->taskProvider->get(AddOrUpdateProductModelsTask::class))
        ;
    }
}
