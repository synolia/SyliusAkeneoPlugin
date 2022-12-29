<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

abstract class AbstractTaskEvent extends Event
{
    public function __construct(protected string $task, protected PipelinePayloadInterface $payload)
    {
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function getPayload(): PipelinePayloadInterface
    {
        return $this->payload;
    }
}
