<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\Family\ProcessFamilyTask;
use Synolia\SyliusAkeneoPlugin\Task\Family\SetupFamilyTask;
use Synolia\SyliusAkeneoPlugin\Task\Family\TearDownFamilyTask;

final class FamilyPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(SetupFamilyTask::class))
            ->pipe($this->taskProvider->get(ProcessFamilyTask::class))
            ->pipe($this->taskProvider->get(TearDownFamilyTask::class))
        ;
    }
}
