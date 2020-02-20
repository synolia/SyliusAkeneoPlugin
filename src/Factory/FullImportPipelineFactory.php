<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;

final class FullImportPipelineFactory extends AbstractPipelineFactory
{
    public function createFullImportPipeline(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe((new CategoryPipelineFactory($this->taskProvider))->create())
            ->pipe((new AttributePipelineFactory($this->taskProvider))->create())
            ->pipe((new OptionPipelineFactory($this->taskProvider))->create())
            ->pipe((new AssetPipelineFactory($this->taskProvider))->create())
            ->pipe((new VariantPipelineFactory($this->taskProvider))->create())
            ->pipe((new ProductPipelineFactory($this->taskProvider))->create())
            ->pipe((new ImagePipelineFactory($this->taskProvider))->create())
        ;
    }
}
