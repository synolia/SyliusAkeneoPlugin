<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;

abstract class AbstractPipelineFactory implements PipelineFactoryInterface
{
    protected AkeneoTaskProvider $taskProvider;

    protected EventDispatcherInterface $dispatcher;

    public function __construct(
        AkeneoTaskProvider $taskProvider,
        EventDispatcherInterface $dispatcher
    ) {
        $this->taskProvider = $taskProvider;
        $this->dispatcher = $dispatcher;
    }
}
