<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Payload\CommandContextIsNullException;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Handler\Task\TaskHandlerProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\TaskHandlerTrait;

final class ProcessAttributeTask implements AkeneoTaskInterface
{
    use TaskHandlerTrait{
        TaskHandlerTrait::__construct as private __taskHandlerConstruct;
    }

    public function __construct(
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private TaskHandlerProviderInterface $taskHandlerProvider,
    ) {
        $this->__taskHandlerConstruct($taskHandlerProvider);
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $queryParameters = [];
        $this->logger->debug(self::class);

        if ($payload->isContinue()) {
            $this->continue($payload);

            return $payload;
        }

        try {
            $event = new FilterEvent($payload->getCommandContext());
            $this->eventDispatcher->dispatch($event);

            $queryParameters['search'] = $event->getFilters();
            $queryParameters['with_table_select_options'] = true;
        } catch (CommandContextIsNullException) {
        } finally {
            $this->logger->notice('Filters', $queryParameters);
        }

        $queryParameters = \array_merge_recursive($queryParameters, $payload->getCustomFilters());

        $page = $payload->getAkeneoPimClient()->getAttributeApi()->listPerPage(
            $this->apiConnectionProvider->get()->getPaginationSize(),
            true,
            $queryParameters,
        );

        $this->handle($payload, $page);

        return $payload;
    }
}
