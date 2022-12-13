<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractProcessTask;

final class ProcessAttributeTask extends AbstractProcessTask
{
    private ApiConnectionProviderInterface $apiConnectionProvider;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        ProcessManagerInterface $processManager,
        BatchAttributesTask $task,
        ApiConnectionProviderInterface $apiConnectionProvider,
        EventDispatcherInterface $eventDispatcher,
        string $projectDir
    ) {
        parent::__construct($entityManager, $processManager, $task, $akeneoLogger, $projectDir);

        $this->apiConnectionProvider = $apiConnectionProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $payload
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

        $page = $payload->getAkeneoPimClient()->getAttributeApi()->listPerPage(
            $this->apiConnectionProvider->get()->getPaginationSize(),
            true,
            $queryParameters
        );

        $this->handle($payload, $page);
        $this->processManager->waitForAllProcesses();

        return $payload;
    }

    protected function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new AttributePayload($payload->getAkeneoPimClient(), $commandContext);
    }
}
