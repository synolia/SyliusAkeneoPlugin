<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\AddAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\MatchAttributeTypesTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\UpdateAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\UpdateFamiliesTask;

final class AttributePipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveAttributesTask::class))
            ->pipe($this->taskProvider->get(\Synolia\SyliusAkeneoPlugin\Task\Attribute\MatchPimCodeWithEntityTask::class))
            ->pipe($this->taskProvider->get(MatchAttributeTypesTask::class))
            ->pipe($this->taskProvider->get(AddAttributesTask::class))
            ->pipe($this->taskProvider->get(UpdateAttributesTask::class))
            ->pipe($this->taskProvider->get(UpdateFamiliesTask::class))
            ;
    }
}
