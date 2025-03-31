<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\Payload;

use Symfony\Contracts\EventDispatcher\Event;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

final class PreHandlePayloadEvent extends Event
{
    public function __construct(protected PipelinePayloadInterface $payload)
    {
    }

    public function getPayload(): PipelinePayloadInterface
    {
        return $this->payload;
    }
}
