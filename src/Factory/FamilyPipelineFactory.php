<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddFamilyVariationAxeTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddProductGroupsTask;

final class FamilyPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(AddProductGroupsTask::class))
            ->pipe($this->taskProvider->get(AddFamilyVariationAxeTask::class))
        ;
    }
}
