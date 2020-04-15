<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\Image\AssociateImagesToProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Image\DetectConfigurableTask;
use Synolia\SyliusAkeneoPlugin\Task\Image\MatchPimCodeWithEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Image\MoveImagesTask;
use Synolia\SyliusAkeneoPlugin\Task\Image\RetrieveImagesTask;

final class ImagePipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveImagesTask::class))
            ->pipe($this->taskProvider->get(DetectConfigurableTask::class))
            ->pipe($this->taskProvider->get(MoveImagesTask::class))
            ->pipe($this->taskProvider->get(MatchPimCodeWithEntityTask::class))
            ->pipe($this->taskProvider->get(AssociateImagesToProductsTask::class))
        ;
    }
}
