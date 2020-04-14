<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;

final class FullImportPipelineFactory extends AbstractPipelineFactory
{
    public function createFullImportPipeline(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe((new CategoryPipelineFactory($this->taskProvider, $this->dispatcher))->create())
            ->pipe((new AttributePipelineFactory($this->taskProvider, $this->dispatcher))->create())
            ->pipe((new AttributeOptionPipelineFactory($this->taskProvider, $this->dispatcher))->create())
            ->pipe((new ProductModelPipelineFactory($this->taskProvider, $this->dispatcher))->create())
            ->pipe((new ProductPipelineFactory($this->taskProvider, $this->dispatcher))->create())
            ->pipe((new ImagePipelineFactory($this->taskProvider, $this->dispatcher))->create())
        ;
    }
}
