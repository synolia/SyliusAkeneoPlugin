<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Handler\Task;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Synolia\SyliusAkeneoPlugin\Manager\ProcessManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Task\Batch\BatchTaskProvider;
use Synolia\SyliusAkeneoPlugin\Provider\Task\Batch\Payload\CommandContext\PayloadBatchTaskProvider;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SymfonyProcessTaskHandler implements TaskHandlerInterface
{
    public const HANDLER_CODE = 'process';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ProcessManagerInterface $processManager,
        protected LoggerInterface $akeneoLogger,
        private string $projectDir,
        private PayloadBatchTaskProvider $payloadBatchTaskProvider,
        private BatchTaskProvider $batchTaskProvider,
    ) {
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload->getHandler() === self::HANDLER_CODE;
    }

    public function batch(
        PipelinePayloadInterface $pipelinePayload,
        array $ids,
    ): void {
        if ($pipelinePayload->allowParallel()) {
            $processArguments = [
                'php',
                'bin/console',
                $pipelinePayload->getCommandName(),
                implode(',', $ids),
            ];

            if ('' !== $pipelinePayload->getVerbosityArgument()) {
                $processArguments[] = $pipelinePayload->getVerbosityArgument();
            }

            $process = new Process($processArguments, $this->projectDir);
            $process->setTimeout(null);
            $process->setIdleTimeout(null);
            $isTtySupported = Process::isTtySupported();
            $process->setTty($isTtySupported);
            $this->processManager->addProcess($process);
            $this->akeneoLogger->info('Added batch process', [
                'ids' => $ids,
            ]);

            return;
        }

        $batchPayload = $this->payloadBatchTaskProvider->createBatchPayload($pipelinePayload);
        $batchPayload->setIds($ids);
        $this->batchTaskProvider->getTask($pipelinePayload)->__invoke($batchPayload);
    }

    public function continue(PipelinePayloadInterface $pipelinePayload): void
    {
        $this->processManager->setInstantProcessing($pipelinePayload->getProcessAsSoonAsPossible());
        $this->processManager->setNumberOfParallelProcesses($pipelinePayload->getMaxRunningProcessQueueSize());

        $totalItemsCount = $this->count($pipelinePayload->getTmpTableName());

        if (0 === $totalItemsCount) {
            return;
        }

        $min = $this->min($pipelinePayload->getTmpTableName());
        $query = $this->prepareSelectBatchIdsQuery($pipelinePayload->getTmpTableName(), $min - 1, $pipelinePayload->getBatchSize());
        /** @var Result $queryResult */
        $queryResult = $query->executeQuery();

        while ($results = $queryResult->fetchAllAssociative()) {
            $ids = [];
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }

            $this->batch($pipelinePayload, $ids);

            $query = $this->prepareSelectBatchIdsQuery($pipelinePayload->getTmpTableName(), (int) $result['id'], $pipelinePayload->getBatchSize());
            $queryResult = $query->executeQuery();
        }

        $this->processManager->startAll();
        $this->processManager->waitForAllProcesses();
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @TODO Probably need to be refactored
     */
    public function handle(
        PipelinePayloadInterface $pipelinePayload,
        iterable|PageInterface $handleType,
    ): void {
        $this->processManager->setInstantProcessing($pipelinePayload->getProcessAsSoonAsPossible());
        $this->processManager->setNumberOfParallelProcesses($pipelinePayload->getMaxRunningProcessQueueSize());

        $count = 0;
        $ids = [];

        if ($handleType instanceof PageInterface) {
            $this->handleByPage($pipelinePayload, $handleType, $count, $ids);
        } else {
            $this->handleByCursor($pipelinePayload, $handleType, $count, $ids);
        }

        if ($count > 0 && count($ids) > 0 && $pipelinePayload->isBatchingAllowed() && $pipelinePayload->getProcessAsSoonAsPossible() && $pipelinePayload->allowParallel()) {
            $this->akeneoLogger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[(is_countable($ids) ? \count($ids) : 0) - 1]]);
            $this->batch($pipelinePayload, $ids);
            $this->processManager->waitForAllProcesses();

            return;
        }

        if ($count > 0 && count($ids) > 0 && $pipelinePayload->isBatchingAllowed() && $pipelinePayload->getProcessAsSoonAsPossible() && !$pipelinePayload->allowParallel()) {
            $pipelinePayload->setIds($ids);
            $this->batchTaskProvider->getTask($pipelinePayload)->__invoke($pipelinePayload);
            $this->processManager->waitForAllProcesses();

            return;
        }

        if ($count > 0 && count($ids) > 0 && !$pipelinePayload->isBatchingAllowed()) {
            $pipelinePayload->setIds($ids);
            $this->batchTaskProvider->getTask($pipelinePayload)->__invoke($pipelinePayload);
            $this->processManager->waitForAllProcesses();

            return;
        }

        if ($count > 0 && !$pipelinePayload->getProcessAsSoonAsPossible()) {
            $this->continue($pipelinePayload);
        }

        $this->processManager->waitForAllProcesses();
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
                    $this->akeneoLogger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
                    $this->batch($payload, $ids);
                    $ids = [];
                }
            }

            $page = $page->getNextPage();
        }
    }

    private function handleByCursor(
        PipelinePayloadInterface $payload,
        iterable $resourceCursor,
        int &$count = 0,
        array &$ids = [],
    ): void {
        foreach ($resourceCursor as $item) {
            $this->akeneoLogger->info('Processing item ' . ($item['identifier'] ?? $item['code']));
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
                $this->akeneoLogger->notice('Batching', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
                $this->batch($payload, $ids);
                $ids = [];
            }
        }
    }

    public function setUp(PipelinePayloadInterface $pipelinePayload): PipelinePayloadInterface
    {
        if ($pipelinePayload->isContinue()) {
            $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
            $tableExist = $schemaManager->tablesExist([$pipelinePayload->getTmpTableName()]);

            if (true === $tableExist) {
                return $pipelinePayload;
            }
        }

        $this->tearDown($pipelinePayload);

        $query = sprintf(
            'CREATE TABLE `%s` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `values` JSON NULL,
              PRIMARY KEY (`id`));',
            $pipelinePayload->getTmpTableName(),
        );
        $this->entityManager->getConnection()->executeStatement($query);

        return $pipelinePayload;
    }

    public function tearDown(PipelinePayloadInterface $pipelinePayload): PipelinePayloadInterface
    {
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        $exists = $schemaManager->tablesExist([$pipelinePayload->getTmpTableName()]);

        if ($exists) {
            $schemaManager->dropTable($pipelinePayload->getTmpTableName());
        }

        return $pipelinePayload;
    }

    private function count(string $tableName): int
    {
        /** @var Result $query */
        $query = $this->entityManager->getConnection()->prepare(sprintf(
            'SELECT count(id) FROM `%s`',
            $tableName,
        ))->executeQuery();

        return (int) current($query->fetch());
    }

    private function min(string $tableName): int
    {
        /** @var Result $query */
        $query = $this->entityManager->getConnection()->prepare(sprintf(
            'SELECT id FROM `%s` ORDER BY id ASC LIMIT 1',
            $tableName,
        ))->executeQuery();

        return (int) current($query->fetch());
    }

    private function prepareSelectBatchIdsQuery(
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
}
