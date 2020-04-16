<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Pipeline;

use League\Pipeline\ProcessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\AfterTaskEvent;
use Synolia\SyliusAkeneoPlugin\Event\BeforeTaskEvent;

class Processor implements ProcessorInterface
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function process($payload, callable ...$stages)
    {
        foreach ($stages as $stage) {
            if (\is_object($stage)) {
                $beforeEvent = new BeforeTaskEvent(\get_class($stage), $payload);
                $this->dispatcher->dispatch($beforeEvent);
                $payload = $beforeEvent->getPayload();
            }

            $payload = $stage($payload);

            if (\is_object($stage)) {
                $afterEvent = new AfterTaskEvent(\get_class($stage), $payload);
                $this->dispatcher->dispatch($afterEvent);
                $payload = $afterEvent->getPayload();
            }
        }

        return $payload;
    }
}
