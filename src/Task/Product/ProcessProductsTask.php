<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Payload\CommandContextIsNullException;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilterInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractProcessTask;

final class ProcessProductsTask extends AbstractProcessTask
{
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        ProcessManagerInterface $processManager,
        BatchProductsTask $task,
        private ProductFilterInterface $productFilter,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private EventDispatcherInterface $eventDispatcher,
        string $projectDir,
    ) {
        parent::__construct($entityManager, $processManager, $task, $akeneoLogger, $projectDir);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @TODO Probably need to be refactored
     *
     * @param ProductPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);

        if ($payload->isContinue()) {
            $this->process($payload);

            return $payload;
        }

        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        $queryParameters = $this->productFilter->getProductFilters();
        $queryParameters['pagination_type'] = 'search_after';

        try {
            $event = new FilterEvent($payload->getCommandContext());
            $this->eventDispatcher->dispatch($event);

            $queryParameters['search'] = array_merge($queryParameters['search'] ?? [], $event->getFilters());
        } catch (CommandContextIsNullException) {
        }

        /** @var \Akeneo\Pim\ApiClient\Pagination\PageInterface|null $resources */
        $resources = $payload->getAkeneoPimClient()->getProductApi()->listPerPage(
            $this->apiConnectionProvider->get()->getPaginationSize(),
            true,
            $queryParameters,
        );

        if (!$resources instanceof Page) {
            return $payload;
        }

        $count = 0;
        $ids = [];

        $this->handleProducts($payload, $resources, $count, $ids);

        if ($count > 0 && count($ids) > 0 && $payload->isBatchingAllowed() && $payload->getProcessAsSoonAsPossible() && $payload->allowParallel()) {
            $this->logger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[(is_countable($ids) ? \count($ids) : 0) - 1]]);
            $this->batch($payload, $ids);
        }

        if ($count > 0 && count($ids) > 0 && $payload->isBatchingAllowed() && $payload->getProcessAsSoonAsPossible() && !$payload->allowParallel()) {
            $payload->setIds($ids);
            $this->task->__invoke($payload);
        }

        if ($count > 0 && count($ids) > 0 && !$payload->isBatchingAllowed()) {
            $payload->setIds($ids);
            $this->task->__invoke($payload);
        }

        if ($count > 0 && !$payload->getProcessAsSoonAsPossible()) {
            $this->process($payload);
        }

        $this->processManager->waitForAllProcesses();

        return $payload;
    }

    private function handleProducts(
        PipelinePayloadInterface $payload,
        PageInterface $page,
        int &$count = 0,
        array &$ids = [],
    ): void {
        while (
            ($page instanceof Page && $page->hasNextPage()) ||
            ($page instanceof Page && !$page->hasPreviousPage()) ||
            $page instanceof Page
        ) {
            foreach ($page->getItems() as $item) {
                $sql = sprintf(
                    'INSERT INTO `%s` (`values`, `is_simple`) VALUES (:values, :is_simple);',
                    ProductPayload::TEMP_AKENEO_TABLE_NAME,
                );

                $stmt = $this->entityManager->getConnection()->prepare($sql);
                $stmt->bindValue('values', json_encode($item, \JSON_THROW_ON_ERROR));
                $stmt->bindValue('is_simple', null === $item['parent'], ParameterType::BOOLEAN);
                $stmt->execute();
                ++$count;

                $ids[] = $this->entityManager->getConnection()->lastInsertId();

                if ($payload->getProcessAsSoonAsPossible() && $payload->allowParallel() && 0 === $count % $payload->getBatchSize()) {
                    $this->logger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
                    $this->batch($payload, $ids);
                    $ids = [];
                }
            }

            $page = $page->getNextPage();
        }
    }

    protected function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new ProductPayload($payload->getAkeneoPimClient(), $commandContext);
    }
}
