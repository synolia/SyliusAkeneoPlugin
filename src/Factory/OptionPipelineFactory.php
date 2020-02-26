<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Option\CreateUpdateDeleteTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\RetrieveOptionsTask;

final class OptionPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveOptionsTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateDeleteTask::class));
    }
}
