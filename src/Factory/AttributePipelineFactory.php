<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\ProcessAttributeTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\SetupAttributeTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\TearDownAttributeTask;

final class AttributePipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(SetupAttributeTask::class))
            ->pipe($this->taskProvider->get(ProcessAttributeTask::class))
            ->pipe($this->taskProvider->get(TearDownAttributeTask::class))
        ;
    }
}
