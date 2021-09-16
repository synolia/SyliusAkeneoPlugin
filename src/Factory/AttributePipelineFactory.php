<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\SetupAttributeTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\TearDownAttributeTask;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\CreateUpdateTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\DeleteTask;

final class AttributePipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(SetupAttributeTask::class))
            ->pipe($this->taskProvider->get(RetrieveAttributesTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateEntityTask::class))
            // AttributeOption
            ->pipe($this->taskProvider->get(RetrieveOptionsTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateDeleteTask::class))
            // Option
            ->pipe($this->taskProvider->get(CreateUpdateTask::class))
            ->pipe($this->taskProvider->get(DeleteTask::class))
            ->pipe($this->taskProvider->get(TearDownAttributeTask::class))
        ;
    }
}
