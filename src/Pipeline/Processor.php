<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Pipeline;

use League\Pipeline\ProcessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\AfterTaskEvent;
use Synolia\SyliusAkeneoPlugin\Event\BeforeTaskEvent;

final class Processor implements ProcessorInterface
{
    public function __construct(private EventDispatcherInterface $dispatcher)
    {
    }

    public function process($payload, callable ...$stages)
    {
        foreach ($stages as $stage) {
            if (\is_object($stage)) {
                $beforeEvent = new BeforeTaskEvent($stage::class, $payload);
                $this->dispatcher->dispatch($beforeEvent);
                $payload = $beforeEvent->getPayload();
            }

            $payload = $stage($payload);

            if (\is_object($stage)) {
                $afterEvent = new AfterTaskEvent($stage::class, $payload);
                $this->dispatcher->dispatch($afterEvent);
                $payload = $afterEvent->getPayload();
            }
        }

        return $payload;
    }
}
