<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Payload\CommandContextIsNullException;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilter;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractProcessTask;

final class ProcessProductModelsTask extends AbstractProcessTask
{
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        ProcessManagerInterface $processManager,
        BatchProductModelTask $task,
        private ProductFilter $productFilter,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private EventDispatcherInterface $eventDispatcher,
        string $projectDir,
    ) {
        parent::__construct($entityManager, $processManager, $task, $akeneoLogger, $projectDir);
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);

        if ($payload->isContinue()) {
            $this->process($payload);

            return $payload;
        }

        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        $queryParameters = $this->productFilter->getProductModelFilters();

        try {
            $event = new FilterEvent($payload->getCommandContext());
            $this->eventDispatcher->dispatch($event);

            $queryParameters['search'] = array_merge($queryParameters['search'], $event->getFilters());
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

        return new ProductModelPayload($payload->getAkeneoPimClient(), $commandContext);
    }
}
