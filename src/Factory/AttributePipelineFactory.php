<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\DeleteEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;

final class AttributePipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveAttributesTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateEntityTask::class))
            ->pipe($this->taskProvider->get(DeleteEntityTask::class))
        ;
    }
}
