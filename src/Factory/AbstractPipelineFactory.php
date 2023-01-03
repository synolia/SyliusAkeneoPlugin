<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;

abstract class AbstractPipelineFactory implements PipelineFactoryInterface
{
    public function __construct(protected TaskProvider $taskProvider, protected EventDispatcherInterface $dispatcher)
    {
    }
}
