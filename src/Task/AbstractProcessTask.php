<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Manager\ProcessManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Throwable;

abstract class AbstractProcessTask implements AkeneoTaskInterface
{
    private int $updateCount = 0;

    private int $createCount = 0;

    private string $type;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ProcessManagerInterface $processManager,
        protected BatchTaskInterface $task,
        protected LoggerInterface $logger,
        private string $projectDir,
    ) {
    }

    protected function count(string $tableName): int
    {
        /** @var Result $query */
        $query = $this->entityManager->getConnection()->prepare(sprintf(
            'SELECT count(id) FROM `%s`',
            $tableName,
        ))->executeQuery();

        return (int) current($query->fetch());
    }

    protected function min(string $tableName): int
    {
        /** @var Result $query */
        $query = $this->entityManager->getConnection()->prepare(sprintf(
            'SELECT id FROM `%s` ORDER BY id ASC LIMIT 1',
            $tableName,
        ))->executeQuery();

        return (int) current($query->fetch());
    }

    protected function prepareSelectBatchIdsQuery(
        string $tableName,
        int $from,
        int $limit,
    ): Statement {
        $query = $this->entityManager->getConnection()->prepare(sprintf(
            'SELECT id
             FROM `%s`
             WHERE id > :from
             ORDER BY id ASC
             LIMIT :limit',
            $tableName,
        ));
        $query->bindValue('from', $from, ParameterType::INTEGER);
        $query->bindValue('limit', $limit, ParameterType::INTEGER);

        return $query;
    }

    protected function batch(
        PipelinePayloadInterface $payload,
        array $ids,
    ): void {
        if ($payload->allowParallel()) {
            $processArguments = [
                'php',
                'bin/console',
                $payload->getCommandName(),
                implode(',', $ids),
            ];

            if ('' !== $payload->getVerbosityArgument()) {
                $processArguments[] = $payload->getVerbosityArgument();
            }

            $process = new Process($processArguments, $this->projectDir);
            $process->setTimeout(null);
            $process->setIdleTimeout(null);
            $isTtySupported = Process::isTtySupported();
            $process->setTty($isTtySupported);
            $this->processManager->addProcess($process);
            $this->logger->info('Added batch process', [
                'ids' => $ids,
            ]);

            return;
        }

        $batchPayload = $this->createBatchPayload($payload);
        $batchPayload->setIds($ids);
        $this->task->__invoke($batchPayload);
    }

    abstract protected function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface;

    protected function process(PipelinePayloadInterface $initialPayload): void
    {
        $this->processManager->setInstantProcessing($initialPayload->getProcessAsSoonAsPossible());
        $this->processManager->setNumberOfParallelProcesses($initialPayload->getMaxRunningProcessQueueSize());

        $this->logger->debug(self::class);
        $this->type = $initialPayload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        try {
            $totalItemsCount = $this->count($initialPayload->getTmpTableName());

            if (0 === $totalItemsCount) {
                return;
            }

            $min = $this->min($initialPayload->getTmpTableName());
            $query = $this->prepareSelectBatchIdsQuery($initialPayload->getTmpTableName(), $min - 1, $initialPayload->getBatchSize());
            /** @var Result $queryResult */
            $queryResult = $query->executeQuery();

            while ($results = $queryResult->fetchAllAssociative()) {
                $ids = [];
                foreach ($results as $result) {
                    $ids[] = $result['id'];
                }

                $this->batch($initialPayload, $ids);

                $query = $this->prepareSelectBatchIdsQuery($initialPayload->getTmpTableName(), (int) $result['id'], $initialPayload->getBatchSize());
                $queryResult = $query->executeQuery();
            }

            $this->processManager->startAll();
        } catch (Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @TODO Probably need to be refactored
     */
    protected function handle(
        PipelinePayloadInterface $payload,
        \Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|\Akeneo\Pim\ApiClient\Pagination\PageInterface $handleType,
    ): void {
        $this->processManager->setInstantProcessing($payload->getProcessAsSoonAsPossible());
        $this->processManager->setNumberOfParallelProcesses($payload->getMaxRunningProcessQueueSize());

        $count = 0;
        $ids = [];

        if ($handleType instanceof PageInterface) {
            $this->handleByPage($payload, $handleType, $count, $ids);
        } elseif ($handleType instanceof ResourceCursorInterface) {
            $this->handleByCursor($payload, $handleType, $count, $ids);
        }

        if ($count > 0 && count($ids) > 0 && $payload->isBatchingAllowed() && $payload->getProcessAsSoonAsPossible() && $payload->allowParallel()) {
            $this->logger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[(is_countable($ids) ? \count($ids) : 0) - 1]]);
            $this->batch($payload, $ids);

            return;
        }

        if ($count > 0 && count($ids) > 0 && $payload->isBatchingAllowed() && $payload->getProcessAsSoonAsPossible() && !$payload->allowParallel()) {
            $payload->setIds($ids);
            $this->task->__invoke($payload);

            return;
        }

        if ($count > 0 && count($ids) > 0 && !$payload->isBatchingAllowed()) {
            $payload->setIds($ids);
            $this->task->__invoke($payload);

            return;
        }

        if ($count > 0 && !$payload->getProcessAsSoonAsPossible()) {
            $this->process($payload);
        }
    }

    private function handleByPage(
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
                ++$count;
                $sql = sprintf(
                    'INSERT INTO `%s` (`values`) VALUES (:values);',
                    $payload->getTmpTableName(),
                );
                $stmt = $this->entityManager->getConnection()->prepare($sql);
                $stmt->bindValue('values', json_encode($item, \JSON_THROW_ON_ERROR));
                $stmt->execute();

                $ids[] = $this->entityManager->getConnection()->lastInsertId();

                if ($payload->isBatchingAllowed() &&
                    $payload->getProcessAsSoonAsPossible() &&
                    0 === $count % $payload->getBatchSize()) {
                    $this->logger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
                    $this->batch($payload, $ids);
                    $ids = [];
                }
            }

            $page = $page->getNextPage();
        }
    }

    private function handleByCursor(
        PipelinePayloadInterface $payload,
        ResourceCursorInterface $resourceCursor,
        int &$count = 0,
        array &$ids = [],
    ): void {
        foreach ($resourceCursor as $item) {
            ++$count;
            $sql = sprintf(
                'INSERT INTO `%s` (`values`) VALUES (:values);',
                $payload->getTmpTableName(),
            );
            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $stmt->bindValue('values', json_encode($item, \JSON_THROW_ON_ERROR));
            $stmt->execute();

            $ids[] = $this->entityManager->getConnection()->lastInsertId();

            if ($payload->isBatchingAllowed() &&
                $payload->getProcessAsSoonAsPossible() &&
                0 === $count % $payload->getBatchSize()) {
                $this->logger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
                $this->batch($payload, $ids);
                $ids = [];
            }
        }
    }
}
