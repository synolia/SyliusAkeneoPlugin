<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;

abstract class AbstractPipelineFactory implements PipelineFactoryInterface
{
    protected TaskProvider $taskProvider;

    protected EventDispatcherInterface $dispatcher;

    public function __construct(
        TaskProvider $taskProvider,
        EventDispatcherInterface $dispatcher
    ) {
        $this->taskProvider = $taskProvider;
        $this->dispatcher = $dispatcher;
    }
}
