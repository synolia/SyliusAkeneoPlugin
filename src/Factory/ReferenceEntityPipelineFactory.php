<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity\CreateUpdateEntityOptionsTask;
use Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity\CreateUpdateReferenceEntityAttributeSubAttributeOptionsTaskTask;
use Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity\CreateUpdateReferenceEntityAttributeSubAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity\RetrieveReferenceEntityAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity\RetrieveReferenceEntityOptionsTask;

final class ReferenceEntityPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveAttributesTask::class))
            ->pipe($this->taskProvider->get(RetrieveReferenceEntityOptionsTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateEntityOptionsTask::class))
            ->pipe($this->taskProvider->get(RetrieveReferenceEntityAttributesTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateReferenceEntityAttributeSubAttributesTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateReferenceEntityAttributeSubAttributeOptionsTaskTask::class))
        ;
    }
}
