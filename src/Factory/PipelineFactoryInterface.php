<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\PipelineInterface;

interface PipelineFactoryInterface
{
    public function create(): PipelineInterface;
}
