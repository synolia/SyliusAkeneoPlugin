<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Option\AssociateOptionsToAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\AssociateValuesToOptionsTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\MatchPimCodeWithEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\RetrieveOptionsTask;

final class OptionPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveOptionsTask::class))
            ->pipe($this->taskProvider->get(MatchPimCodeWithEntityTask::class))
            ->pipe($this->taskProvider->get(AssociateOptionsToAttributesTask::class))
            ->pipe($this->taskProvider->get(AssociateValuesToOptionsTask::class))
        ;
    }
}
