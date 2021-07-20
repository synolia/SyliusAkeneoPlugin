<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\Association\AddAssociationTypeTask;
use Synolia\SyliusAkeneoPlugin\Task\Association\RetrieveAssociationTask;

final class AssociationTypePipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveAssociationTask::class))
            ->pipe($this->taskProvider->get(AddAssociationTypeTask::class))
            ;
    }
}
