<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Synolia\SyliusAkeneoPlugin\Event\BeforeTaskEvent;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Pipeline\DummyPayload;

final class BeforeTaskSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            BeforeTaskEvent::class => [
                ['processEvent', 0],
            ],
        ];
    }

    public function processEvent(BeforeTaskEvent $event)
    {
        /** @var DummyPayload $payload */
        $payload = $event->getPayload();
        if (method_exists($payload, 'addLog')) {
            $payload->addLog($event::NAME);
        }
    }
}
