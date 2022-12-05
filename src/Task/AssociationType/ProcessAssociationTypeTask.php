<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AssociationType;

use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractProcessTask;

final class ProcessAssociationTypeTask extends AbstractProcessTask
{
    private ApiConnectionProviderInterface $apiConnectionProvider;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        ProcessManagerInterface $processManager,
        BatchAssociationTypesTask $task,
        ApiConnectionProviderInterface $apiConnectionProvider,
        EventDispatcherInterface $eventDispatcher,
        string $projectDir
    ) {
        parent::__construct($entityManager, $processManager, $task, $akeneoLogger, $projectDir);

        $this->apiConnectionProvider = $apiConnectionProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param AssociationTypePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);

        if ($payload->isContinue()) {
            $this->process($payload);

            return $payload;
        }

        $event = new FilterEvent($payload->getCommandContext());
        $this->eventDispatcher->dispatch($event);

        $queryParameters['search'] = $event->getFilters();

        $page = $payload->getAkeneoPimClient()->getAssociationTypeApi()->listPerPage(
            $this->apiConnectionProvider->get()->getPaginationSize(),
            false,
            $queryParameters
        );

        $this->handle($payload, $page);

        return $payload;
    }

    protected function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new AssociationTypePayload($payload->getAkeneoPimClient(), $commandContext);
    }
}
