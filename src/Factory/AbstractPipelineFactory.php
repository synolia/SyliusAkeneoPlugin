<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;

abstract class AbstractPipelineFactory
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    protected $taskProvider;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(
        AkeneoTaskProvider $taskProvider,
        EventDispatcherInterface $dispatcher
    ) {
        $this->taskProvider = $taskProvider;
        $this->dispatcher = $dispatcher;
    }
}
