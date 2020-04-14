<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Synolia\SyliusAkeneoPlugin\Event\AfterTaskEvent;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Pipeline\DummyPayload;

class AfterTaskSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            AfterTaskEvent::class => [
                ['processEvent', 0],
            ],
        ];
    }

    public function processEvent(AfterTaskEvent $event)
    {
        /** @var DummyPayload $payload */
        $payload = $event->getPayload();
        if (\method_exists($payload, 'addLog')) {
            $payload->addLog($event::NAME);
        }
    }
}
