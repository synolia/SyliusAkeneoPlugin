<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddFamilyVariationAxeTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddOrUpdateProductModelTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\RetrieveProductModelsTask;

final class ProductModelPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveProductModelsTask::class))
            ->pipe($this->taskProvider->get(AddOrUpdateProductModelTask::class))
            ->pipe($this->taskProvider->get(AddFamilyVariationAxeTask::class))
        ;
    }
}
