<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\ProcessProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\SetupProductModelTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\TearDownProductModelTask;

final class ProductModelPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(SetupProductModelTask::class))
            ->pipe($this->taskProvider->get(ProcessProductModelsTask::class))
            ->pipe($this->taskProvider->get(TearDownProductModelTask::class))
        ;
    }
}
