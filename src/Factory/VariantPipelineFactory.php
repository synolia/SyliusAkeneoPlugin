<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Variant\RetrieveVariantsTask;
use Synolia\SyliusAkeneoPlugin\Task\Variant\UpdateVariantsTask;

final class VariantPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveVariantsTask::class))
            ->pipe($this->taskProvider->get(UpdateVariantsTask::class))
        ;
    }
}
