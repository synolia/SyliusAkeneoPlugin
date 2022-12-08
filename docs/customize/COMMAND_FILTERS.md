# Use command filters

* In each product command an event [`FilterEvent`](../../src/Event/FilterEvent.php) is dispatched.

These events have three functions :
* `getCommandContext()` : return the [`CommandContext`](../../src/Command/Context/CommandContextInterface.php)
* `getFilters()` : return the list of filters
* `addFilter()` : to add filter

This event will be modified a search query parameter when a client get elements from Akeneo.

## Example

If your akeneo has an attribute with code `provider` you can add filter with the previous event.

### CLI Command

`bin/console akeneo:import:products -p --filter provider=synolia`

This command will be launch the symfony event dispatcher, and the following listener will be add a search parameter for akeneo

```php
<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;

class ProviderFilterSubscriber implements EventSubscriberInterface
{
    public function onFilterEvent(FilterEvent $event)
    {
        $commandFilters = $event->getCommandContext()->getFilters();

        $filters = [];

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
            $event->addFilter(
                'provider_type',
                [
                    [
                        'operator' => '=',
                        'value' => $commandFilter['provider'],
                    ],
                ]
            );
        }
    }
}
```

With this, you request url from Sylius to Akeneo will be :

`/api/rest/v1/products?search={"provider_type":[{"operator":"=","value":"synolia"}]}`

For more operator, you can go on [Akeneo documentation](https://api.akeneo.com/documentation/filter.html).