<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\AssociationType\ProcessAssociationTypeTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;
use Synolia\SyliusAkeneoPlugin\Task\TearDownTask;

final class AssociationTypePipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(SetupTask::class))
            ->pipe($this->taskProvider->get(ProcessAssociationTypeTask::class))
            ->pipe($this->taskProvider->get(TearDownTask::class))
        ;
    }
}
