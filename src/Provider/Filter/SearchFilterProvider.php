<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Filter;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Payload\CommandContextIsNullException;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Filter\Resource\ResourceSearchFilterProviderInterface;

class SearchFilterProvider implements SearchFilterProviderInterface
{
    /**
     * @param ResourceSearchFilterProviderInterface[] $resourceSearchFilterProviders
     */
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $akeneoLogger,
        #[TaggedIterator(ResourceSearchFilterProviderInterface::class)]
        private iterable $resourceSearchFilterProviders = [],
    ) {
    }

    public function get(PayloadInterface $payload): array
    {
        $queryParameters = $this->getBaseQueryParams($payload);
        $queryParameters['page'] = $payload->getFromPage();

        try {
            $event = new FilterEvent($payload->getCommandContext());
            $this->eventDispatcher->dispatch($event);

            $queryParameters['search'] = array_merge($queryParameters['search'] ?? [], $event->getFilters());
        } catch (CommandContextIsNullException) {
            $queryParameters = [];
        }

        $queryParameters = array_merge_recursive($queryParameters, $payload->getCustomFilters());

        $this->akeneoLogger->notice('Filters', $queryParameters);

        return $queryParameters;
    }

    private function getBaseQueryParams(PayloadInterface $payload): array
    {
        foreach ($this->resourceSearchFilterProviders as $resourceSearchFilterProvider) {
            if ($resourceSearchFilterProvider->support($payload)) {
                return $resourceSearchFilterProvider->get($payload);
            }
        }

        return ['search' => []];
    }
}
