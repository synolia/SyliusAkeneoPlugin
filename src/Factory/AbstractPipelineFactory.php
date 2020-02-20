<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;

abstract class AbstractPipelineFactory
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    protected $taskProvider;

    public function __construct(AkeneoTaskProvider $taskProvider)
    {
        $this->taskProvider = $taskProvider;
    }
}
