<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\Product\ProcessProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\SetupProductTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\TearDownProductTask;

final class ProductPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(SetupProductTask::class))
            ->pipe($this->taskProvider->get(ProcessProductsTask::class))
            ->pipe($this->taskProvider->get(TearDownProductTask::class))
        ;
    }
}
