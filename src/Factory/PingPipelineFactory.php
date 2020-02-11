<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\PingTask;

final class PingPipelineFactory
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    public function __construct(AkeneoTaskProvider $taskProvider)
    {
        $this->taskProvider = $taskProvider;
    }

    public function create(): Pipeline
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(PingTask::class))
        ;
    }
}
