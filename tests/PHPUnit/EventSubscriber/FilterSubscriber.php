<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;

class FilterSubscriber implements EventSubscriberInterface
{
    public function onFilterEvent(FilterEvent $event)
    {
        $commandFilters = $event->getCommandContext()->getFilters();

        foreach ($commandFilters as $commandFilter) {
            parse_str($commandFilter, $commandFilter);

            $this->prepareFilter($commandFilter, $event);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            FilterEvent::class => 'onFilterEvent',
        ];
    }

    private function prepareFilter(array $commandFilter, FilterEvent $event): void
    {
        if (!empty($commandFilter['provider'])) {
            $event->getCommandContext()->getOutput()->writeln('Provider: ' . $commandFilter['provider']);
        }
    }
}