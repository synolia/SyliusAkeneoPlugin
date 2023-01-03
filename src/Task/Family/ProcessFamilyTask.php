<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Family;

use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Payload\CommandContextIsNullException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractProcessTask;

final class ProcessFamilyTask extends AbstractProcessTask
{
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        ProcessManagerInterface $processManager,
        BatchFamilyTask $task,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private EventDispatcherInterface $eventDispatcher,
        string $projectDir,
    ) {
        parent::__construct($entityManager, $processManager, $task, $akeneoLogger, $projectDir);
    }

    /**
     * @param FamilyPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $queryParameters = [];
        $this->logger->debug(self::class);

        if ($payload->isContinue()) {
            $this->process($payload);

            return $payload;
        }

        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        try {
            $event = new FilterEvent($payload->getCommandContext());
            $this->eventDispatcher->dispatch($event);

            $queryParameters['search'] = $event->getFilters();
        } catch (CommandContextIsNullException) {
            $queryParameters = [];
        }

        $resources = $payload->getAkeneoPimClient()->getProductModelApi()->all(
            $this->apiConnectionProvider->get()->getPaginationSize(),
            $queryParameters,
        );

        $this->handle($payload, $resources);
        $this->processManager->waitForAllProcesses();

        return $payload;
    }

    protected function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new FamilyPayload($payload->getAkeneoPimClient(), $commandContext);
    }
}
